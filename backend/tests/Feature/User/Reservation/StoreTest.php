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
        'duration' => 60,
    ]);

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->payload = [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'services' => [['service_id' => $this->service->id]],
        'start_time' => now()->addDay()->toDateTimeString(),
        'finish_time' => now()->addDay()->addHour()->toDateTimeString(),
    ];
});

test('user should be authenticated to store a reservation', function () {
    $this->postJson(route('user.reservation.store'), $this->payload)->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->user, ['*'], 'expert');
    $this->postJson(route('user.reservation.store'), $this->payload)->assertUnauthorized();
});

test('store validates the required fields', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->postJson(route('user.reservation.store'), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['expert_id', 'hall_id', 'services', 'start_time', 'finish_time']);
});

test('store rejects a finish time that is not after the start time', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), [
        ...$this->payload,
        'finish_time' => $this->payload['start_time'],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['finish_time']);
});

test('store rejects a service not offered by the hall', function () {
    $foreignService = Service::factory()->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), [
        ...$this->payload,
        'services' => [['service_id' => $foreignService->id]],
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('reservations', 0);
});

test('store rejects an expert who is not staffed at the hall', function () {
    $strangerExpert = Expert::factory()->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), [
        ...$this->payload,
        'expert_id' => $strangerExpert->id,
    ]);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.outside_working_hours'));
    $this->assertDatabaseCount('reservations', 0);
});

test('store rejects a time outside the expert working hours', function () {
    $this->expertHall->workingHours()->delete();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.outside_working_hours'));
    $this->assertDatabaseCount('reservations', 0);
});

test('store rejects a time that overlaps the expert\'s existing reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
    $this->assertDatabaseCount('reservations', 1);
});

test('store rejects a time that overlaps the user\'s existing reservation at another hall', function () {
    Reservation::factory()->create([
        'user_id' => $this->user->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
    $this->assertDatabaseCount('reservations', 1);
});

test('user can store a reservation priced from hall services', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), $this->payload);

    $response->assertOk();
    $response->assertExactJson(['message' => __('messages.successful')]);

    $this->assertDatabaseHas('reservations', [
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'total_price' => 200000,
        'discount_amount' => 0,
        'discount_id' => null,
    ]);

    $this->assertDatabaseHas('reservation_services', [
        'service_id' => $this->service->id,
        'service_name' => $this->service->sub_cat_name,
        'price' => 200000,
        'duration' => 60,
    ]);
});

test('store auto-applies the best matching discount to the server total', function () {
    Discount::factory()->holiday()->percentage(10)->create([
        'hall_id' => $this->hall->id,
        'starts_at' => now()->subDays(2)->toDateString(),
        'ends_at' => now()->addDays(2)->toDateString(),
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->postJson(route('user.reservation.store'), $this->payload);

    $response->assertOk();

    $this->assertDatabaseHas('reservations', [
        'user_id' => $this->user->id,
        'discount_amount' => 20000,
        'total_price' => 180000,
    ]);
});
