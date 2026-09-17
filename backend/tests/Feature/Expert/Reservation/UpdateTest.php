<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();

    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 90,
    ]);

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->owner->id,
        'hall_id' => $this->hall->id,
    ]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->reservation = Reservation::factory()->create([
        'expert_id' => $this->owner->id,
        'hall_id' => $this->hall->id,
        'total_price' => 5,
    ]);

    $this->payload = [
        'services' => [['service_id' => $this->service->id]],
        'start_time' => now()->addDays(2)->toDateTimeString(),
        'finish_time' => now()->addDays(2)->addHour()->toDateTimeString(),
    ];
});

test('expert should be authenticated to update a reservation', function () {
    $this->postJson(
        route('expert.reservation.update', ['hall' => $this->hall->id, 'reservation' => $this->reservation->id]),
        $this->payload
    )->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(
        route('expert.reservation.update', ['hall' => $this->hall->id, 'reservation' => $this->reservation->id]),
        $this->payload
    )->assertUnauthorized();
});

test('expert without access to the hall is forbidden', function () {
    $stranger = Expert::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'expert');
    $this->postJson(
        route('expert.reservation.update', ['hall' => $this->hall->id, 'reservation' => $this->reservation->id]),
        $this->payload
    )->assertForbidden();
});

test('updating with a reservation from another hall returns 404', function () {
    $otherHall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(
        route('expert.reservation.update', ['hall' => $otherHall->id, 'reservation' => $this->reservation->id]),
        $this->payload
    )->assertNotFound();
});

test('hall owner can update a reservation and reprice from hall services', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), $this->payload);

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

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), $this->payload);

    $response->assertOk();

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'discount_id' => $discount->id,
        'discount_amount' => 20000,
        'total_price' => 180000,
    ]);
});

test('update rejects a finish time that is not after the start time', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), [
        ...$this->payload,
        'finish_time' => $this->payload['start_time'],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['finish_time']);
});

test('update rejects a time outside the expert working hours', function () {
    $this->expertHall->workingHours()->delete();

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.outside_working_hours'));
});

test('update does not conflict with the reservation being updated itself', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), [
        ...$this->payload,
        'start_time' => $this->reservation->start_time->toDateTimeString(),
        'finish_time' => $this->reservation->finish_time->toDateTimeString(),
    ]);

    $response->assertOk();
});

test('update rejects a time that overlaps the expert\'s other reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->owner->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
});

test('update rejects a time that overlaps the same user\'s other reservation', function () {
    Reservation::factory()->create([
        'user_id' => $this->reservation->user_id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.update', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
});
