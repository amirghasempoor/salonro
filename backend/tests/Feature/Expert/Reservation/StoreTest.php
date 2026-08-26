<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;
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
        'duration' => 60,
    ]);

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
