<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\Service;

beforeEach(function () {
    seedReservationStates();

    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');

    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $this->hall->experts()->attach($this->expert->id);

    $this->service = Service::factory()->create();
});

test('expert can create a reservation', function () {
    $this->postJson('/expert/reservations', [
        'phone_number' => '09123456789',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'hall_id' => $this->hall->id,
        'services' => [
            ['id' => $this->service->id, 'name' => $this->service->sub_cat_name, 'price' => 150000, 'duration' => 60],
        ],
        'start_time' => '2026-08-20 10:00:00',
        'finish_time' => '2026-08-20 11:00:00',
        'total_price' => 150000,
    ])->assertOk();

    $this->assertDatabaseHas('reservations', [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => 1,
    ]);
});

test('expert can list reservations of an owned hall', function () {
    Reservation::factory()->count(3)->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson("/expert/reservations?hall_id={$this->hall->id}&filters=[]&sorting=[]")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('expert cannot list reservations of a hall they do not own', function () {
    $otherHall = Hall::factory()->create();

    $this->getJson("/expert/reservations?hall_id={$otherHall->id}&filters=[]&sorting=[]")
        ->assertStatus(403);
});

test('expert can view a reservation', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson("/expert/reservations/{$reservation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $reservation->id);
});

test('expert can update a reservation', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->postJson("/expert/reservations/{$reservation->id}", [
        'services' => [
            ['id' => $this->service->id, 'name' => $this->service->sub_cat_name, 'price' => 200000, 'duration' => 90],
        ],
        'start_time' => '2026-08-21 09:00:00',
        'finish_time' => '2026-08-21 10:30:00',
        'total_price' => 200000,
    ])->assertOk();

    $this->assertDatabaseHas('reservations', ['id' => $reservation->id, 'total_price' => 200000]);
    $this->assertDatabaseHas('reservation_services', [
        'reservation_id' => $reservation->id,
        'service_id' => $this->service->id,
    ]);
});

test('expert can delete a reservation', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->deleteJson("/expert/reservations/{$reservation->id}")->assertOk();

    $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
});
