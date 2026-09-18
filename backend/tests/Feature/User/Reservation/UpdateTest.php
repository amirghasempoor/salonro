<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 90,
    ]);

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->reservation = Reservation::factory()->create([
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'total_price' => 5,
    ]);

    $this->payload = [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'services' => [['service_id' => $this->service->id]],
        'start_time' => now()->addDays(2)->toDateTimeString(),
        'finish_time' => now()->addDays(2)->addHour()->toDateTimeString(),
    ];
});

test('user should be authenticated to update a reservation', function () {
    $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload)->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->user, ['*'], 'expert');
    $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload)->assertUnauthorized();
});

test('update rejects a finish time that is not after the start time', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), [
        ...$this->payload,
        'finish_time' => $this->payload['start_time'],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['finish_time']);
});

test('update rejects a time outside the expert working hours', function () {
    $this->expertHall->workingHours()->delete();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.outside_working_hours'));
});

test('update does not conflict with the reservation being updated itself', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), [
        ...$this->payload,
        'start_time' => $this->reservation->start_time->toDateTimeString(),
        'finish_time' => $this->reservation->finish_time->toDateTimeString(),
    ]);

    $response->assertOk();
});

test('update rejects a time that overlaps the expert\'s other reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
});

test('update rejects a time that overlaps the user\'s other reservation', function () {
    Reservation::factory()->create([
        'user_id' => $this->user->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
});

test('user can update a reservation and reprice from hall services', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload);

    $response->assertOk();
    $response->assertExactJson(['message' => __('messages.successful')]);

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'total_price' => 200000,
        'discount_amount' => 0,
        'start_time' => $this->payload['start_time'],
    ]);

    expect($this->reservation->fresh()->services)->toHaveCount(1);
    $this->assertDatabaseHas('reservation_services', [
        'reservation_id' => $this->reservation->id,
        'service_id' => $this->service->id,
        'price' => 200000,
    ]);
});

test('updating recomputes an already granted discount against the new total', function () {
    $discount = Discount::factory()->holiday()->percentage(10)->create(['hall_id' => $this->hall->id]);
    $this->reservation->update([
        'discount_id' => $discount->id,
        'discount_amount' => 1,
        'total_price' => 1,
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.update', $this->reservation->id), $this->payload);

    $response->assertOk();

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'discount_id' => $discount->id,
        'discount_amount' => 20000,
        'total_price' => 180000,
    ]);
});
