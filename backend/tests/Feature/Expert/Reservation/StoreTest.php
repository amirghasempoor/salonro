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
use Illuminate\Support\Carbon;
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
        'duration' => 60,
    ]);

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->owner->id,
        'hall_id' => $this->hall->id,
    ]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->payload = [
        'phone_number' => '09120000000',
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'services' => [['service_id' => $this->service->id]],
        'start_time' => now()->addDay()->toDateTimeString(),
        'finish_time' => now()->addDay()->addHour()->toDateTimeString(),
    ];
});

test('expert should be authenticated to store a reservation', function () {
    $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload)->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload)->assertUnauthorized();
});

test('expert without access to the hall is forbidden', function () {
    $stranger = Expert::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'expert');
    $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload)->assertForbidden();
});

test('store validates the required fields', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.reservation.store', [$this->hall->id]), [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['phone_number', 'first_name', 'last_name', 'services', 'start_time', 'finish_time']);
});

test('store rejects a service not offered by the hall', function () {
    $foreignService = Service::factory()->create();

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), [
        ...$this->payload,
        'services' => [['service_id' => $foreignService->id]],
    ]);

    $response->assertStatus(422);
    $this->assertDatabaseCount('reservations', 0);
});

test('hall owner can store a reservation priced from hall services', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload);

    $response->assertOk();
    $response->assertExactJson(['message' => __('messages.successful')]);

    $this->assertDatabaseHas('users', [
        'phone_number' => '09120000000',
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
    ]);

    $this->assertDatabaseHas('reservations', [
        'hall_id' => $this->hall->id,
        'expert_id' => $this->owner->id,
        'user_name' => 'Ali Ahmadi',
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

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload);

    $response->assertOk();

    $this->assertDatabaseHas('reservations', [
        'hall_id' => $this->hall->id,
        'discount_amount' => 20000,
        'total_price' => 180000,
    ]);
});

test('store rejects a finish time that is not after the start time', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), [
        ...$this->payload,
        'finish_time' => $this->payload['start_time'],
    ]);

    $response->assertStatus(422)->assertJsonValidationErrors(['finish_time']);
    $this->assertDatabaseCount('reservations', 0);
});

test('store rejects a time outside the expert working hours', function () {
    $this->expertHall->workingHours()->delete();

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.outside_working_hours'));
    $this->assertDatabaseCount('reservations', 0);
});

test('store succeeds when the reservation exactly fits the expert working-hours window', function () {
    $start = Carbon::parse($this->payload['start_time']);
    $finish = Carbon::parse($this->payload['finish_time']);

    $this->expertHall->workingHours()->delete();
    $this->expertHall->workingHours()->create([
        'day' => strtolower($start->format('D')),
        'from' => $start->format('H:i:s'),
        'to' => $finish->format('H:i:s'),
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload);

    $response->assertOk();
});

test('store rejects a time that overlaps the expert\'s existing reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->owner->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
    $this->assertDatabaseCount('reservations', 1);
});

test('store rejects a time that overlaps the same user\'s existing reservation at another hall', function () {
    $existingUser = User::factory()->create(['phone_number' => $this->payload['phone_number']]);

    Reservation::factory()->create([
        'user_id' => $existingUser->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.reservation.store', [$this->hall->id]), $this->payload);

    $response->assertStatus(422);
    $response->assertJsonPath('message', __('messages.reservation_conflict'));
    $this->assertDatabaseCount('reservations', 1);
});
