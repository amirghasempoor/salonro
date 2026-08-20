<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;

beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->actingAs($this->user);

    $this->expert = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 150000,
        'duration' => 60,
    ]);
});

test('user can create a reservation with server-side pricing', function () {
    $this->postJson('/reservations', [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'services' => [['service_id' => $this->service->id]],
        'start_time' => '2026-08-20 10:00:00',
        'finish_time' => '2026-08-20 11:00:00',
    ])->assertOk();

    $this->assertDatabaseHas('reservations', [
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => 1,
    ]);
    $this->assertDatabaseHas('reservation_services', [
        'service_id' => $this->service->id,
        'price' => 150000,
    ]);

    $reservation = Reservation::query()->latest('id')->first();
    expect((int) $reservation->total_price)->toBe(150000);
});

test('user can list own reservations', function () {
    Reservation::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson('/reservations?filters=[]&sorting=[]')
        ->assertOk()
        ->assertJsonCount(3, 'data');
});

test('user sees only own reservations in the list', function () {
    Reservation::factory()->create([
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    Reservation::factory()->create([
        'user_id' => User::factory(),
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson('/reservations?filters=[]&sorting=[]')
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('user can view a reservation', function () {
    $reservation = Reservation::factory()->create([
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->getJson("/reservations/{$reservation->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $reservation->id);
});

test('user can update a reservation and price is recomputed server-side', function () {
    $reservation = Reservation::factory()->create([
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->postJson("/reservations/{$reservation->id}", [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
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

test('user can delete a reservation', function () {
    $reservation = Reservation::factory()->create([
        'user_id' => $this->user->id,
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    $this->deleteJson("/reservations/{$reservation->id}")->assertOk();

    $this->assertDatabaseMissing('reservations', ['id' => $reservation->id]);
});
