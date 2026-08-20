<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;

beforeEach(function () {
    seedReservationStates();

    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');

    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $this->hall->experts()->attach($this->expert->id);

    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 150000,
        'duration' => 60,
    ]);
});

test('expert can create a reservation with server-side pricing', function () {
    $this->postJson("/expert/reservations/{$this->hall->id}", [
        'phone_number' => '09123456789',
        'first_name' => 'John',
        'last_name' => 'Doe',
        'services' => [['service_id' => $this->service->id]],
        'start_time' => '2026-08-20 10:00:00',
        'finish_time' => '2026-08-20 11:00:00',
    ])->assertOk();

    $this->assertDatabaseHas('reservations', [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => 1,
    ]);

    $reservation = Reservation::query()->latest('id')->first();
    expect((int) $reservation->total_price)->toBe(150000);
});

test('expert can list reservations of an owned hall', function () {
    Reservation::factory()->count(3)->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson("/expert/reservations/{$this->hall->id}?filters=[]&sorting=[]")
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('expert cannot list reservations of a hall they do not own', function () {
    $otherHall = Hall::factory()->create();

    $this->getJson("/expert/reservations/{$otherHall->id}?filters=[]&sorting=[]")
        ->assertStatus(403);
});

test('expert can view a reservation', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson("/expert/reservations/details/{$reservation->id}")
        ->assertOk()
        ->assertJsonStructure(['data' => ['expert_name', 'user_name', 'total_price', 'discount_amount', 'hall_name']]);
});

test('expert can update a reservation and price is recomputed server-side', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->postJson("/expert/reservations/{$this->hall->id}/{$reservation->id}", [
        'services' => [['service_id' => $this->service->id]],
        'start_time' => '2026-08-21 09:00:00',
        'finish_time' => '2026-08-21 10:30:00',
    ])->assertOk();

    expect((int) $reservation->fresh()->total_price)->toBe(150000);
    $this->assertDatabaseHas('reservation_services', [
        'reservation_id' => $reservation->id,
        'service_id' => $this->service->id,
    ]);
});

test('expert cannot update a reservation that belongs to another hall', function () {
    $otherHall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $otherHall->id,
    ]);

    $this->postJson("/expert/reservations/{$this->hall->id}/{$reservation->id}", [
        'services' => [['service_id' => $this->service->id]],
        'start_time' => '2026-08-21 09:00:00',
        'finish_time' => '2026-08-21 10:30:00',
    ])->assertStatus(404);
});

test('expert can delete a reservation', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->deleteJson("/expert/reservations/{$this->hall->id}/{$reservation->id}")->assertOk();

    $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
});
