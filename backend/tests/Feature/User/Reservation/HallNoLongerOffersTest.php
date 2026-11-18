<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// A manager can switch a service off or remove it from the hall's catalog while an
// expert still has her own price for it. The customer must not be able to book it.
beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();
    $this->service = Service::factory()->create();

    $this->hallService = HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);

    $this->expertHall = ExpertHall::factory()->create(['expert_id' => $this->expert->id, 'hall_id' => $this->hall->id]);
    seedFullWeekWorkingHours($this->expertHall);

    ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 60,
    ]);

    $this->payload = [
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'services' => [$this->service->id],
        'start_time' => now()->addDay()->toDateTimeString(),
        'finish_time' => now()->addDay()->addHour()->toDateTimeString(),
    ];
});

test('a service the hall offers is bookable', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->postJson(route('user.reservation.store'), $this->payload)->assertOk();
});

test('a service the manager switched off cannot be booked, listed or priced', function () {
    $this->hallService->update(['is_active' => false]);
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->postJson(route('user.reservation.store'), $this->payload)
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.hall_does_not_offer_service'));
    $this->assertDatabaseCount('reservations', 0);

    $this->getJson(route('user.home.hallServices', $this->hall))->assertOk()->assertJsonCount(0, 'data');
    $this->getJson(route('user.reservation.staff', ['hall' => $this->hall->id, 'service_ids' => [$this->service->id]]))
        ->assertOk()->assertJsonCount(0, 'data');
});

test('a service the manager removed from the hall cannot be booked', function () {
    $this->hallService->delete();
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->postJson(route('user.reservation.store'), $this->payload)
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.hall_does_not_offer_service'));
    $this->assertDatabaseCount('reservations', 0);
});

test('switching the service back on makes it bookable again', function () {
    $this->hallService->update(['is_active' => false]);
    $this->hallService->update(['is_active' => true]);
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->postJson(route('user.reservation.store'), $this->payload)->assertOk();
});

test('the expert side refuses the same service too', function () {
    $this->hallService->update(['is_active' => false]);
    Sanctum::actingAs($this->expert, ['*'], 'expert');

    $this->postJson(route('expert.reservation.store', [$this->hall->id]), [
        'phone_number' => '09120000000',
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'services' => [['service_id' => $this->service->id]],
        'start_time' => now()->addDay()->toDateTimeString(),
        'finish_time' => now()->addDay()->addHour()->toDateTimeString(),
    ])->assertStatus(422)->assertJsonPath('message', __('messages.hall_does_not_offer_service'));

    $this->assertDatabaseCount('reservations', 0);
});
