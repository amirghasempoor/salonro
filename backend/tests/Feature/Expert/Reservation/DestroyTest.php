<?php

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\Service;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();
    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->reservation = Reservation::factory()->create([
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
    ]);
});

test('expert should be authenticated to delete a reservation', function () {
    $this->deleteJson(route('expert.reservation.destroy', ['hall' => $this->hall->id, 'reservation' => $this->reservation->id]))
        ->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->deleteJson(route('expert.reservation.destroy', ['hall' => $this->hall->id, 'reservation' => $this->reservation->id]))
        ->assertUnauthorized();
});

test('expert without access to the hall is forbidden', function () {
    $stranger = Expert::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'expert');
    $this->deleteJson(route('expert.reservation.destroy', ['hall' => $this->hall->id, 'reservation' => $this->reservation->id]))
        ->assertForbidden();
});

test('deleting a reservation that does not belong to the hall returns 404', function () {
    $otherHall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->deleteJson(route('expert.reservation.destroy', ['hall' => $otherHall->id, 'reservation' => $this->reservation->id]))
        ->assertNotFound();
});

test('hall owner can cancel a reservation, which is kept as a record', function () {
    $service = Service::factory()->create();
    $this->reservation->services()->attach($service->id, [
        'service_name' => $service->sub_cat_name,
        'price' => 100000,
        'duration' => 60,
    ]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->deleteJson(route('expert.reservation.destroy', [
        'hall' => $this->hall->id,
        'reservation' => $this->reservation->id,
    ]));

    $response->assertOk();
    $response->assertExactJson(['message' => __('messages.successful')]);

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'state_id' => ReservationStates::Cancel->value,
    ]);
    $this->assertDatabaseHas('reservation_services', ['reservation_id' => $this->reservation->id]);
});
