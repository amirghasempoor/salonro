<?php

use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 90,
    ]);

    $this->reservation = Reservation::factory()->create([
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
