<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// A hall, a staff membership or an expert that was switched off cannot be booked.
beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();
    $this->service = Service::factory()->create();

    HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);

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
        'start_time' => now()->addDay()->setTime(10, 0)->toDateTimeString(),
        'finish_time' => now()->addDay()->setTime(11, 0)->toDateTimeString(),
    ];

    Sanctum::actingAs($this->user, ['*'], 'user');
});

function staffUrl($test): string
{
    return route('user.reservation.staff', ['hall' => $test->hall->id, 'service_ids' => [$test->service->id]]);
}

test('an active hall, member and expert can be booked', function () {
    $this->getJson(staffUrl($this))->assertOk()->assertJsonCount(1, 'data');
    $this->postJson(route('user.reservation.store'), $this->payload)->assertOk();
});

test('staff the manager deactivated is not listed or bookable', function () {
    $this->expertHall->update(['is_active' => false]);

    $this->getJson(staffUrl($this))->assertOk()->assertJsonCount(0, 'data');
    $this->postJson(route('user.reservation.store'), $this->payload)->assertStatus(422);
    $this->assertDatabaseCount('reservations', 0);
});

test('an expert who deactivated her profile is not listed or bookable', function () {
    $this->expert->update(['is_active' => false]);

    $this->getJson(staffUrl($this))->assertOk()->assertJsonCount(0, 'data');
    $this->postJson(route('user.reservation.store'), $this->payload)->assertStatus(422);
    $this->assertDatabaseCount('reservations', 0);
});

test('a deactivated hall is hidden and cannot be booked', function () {
    $this->hall->update(['is_active' => false]);

    $this->getJson(route('user.home.hallDetails', $this->hall))->assertNotFound();
    $this->getJson(route('user.home.hallServices', $this->hall))->assertNotFound();
    $this->getJson(staffUrl($this))->assertNotFound();
    $this->postJson(route('user.reservation.store'), $this->payload)->assertNotFound();
    $this->assertDatabaseCount('reservations', 0);
});

test('the expert side refuses a deactivated member and a deactivated hall', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $body = [
        'phone_number' => '09120000000',
        'first_name' => 'Ali',
        'last_name' => 'Ahmadi',
        'services' => [['service_id' => $this->service->id]],
        'start_time' => $this->payload['start_time'],
        'finish_time' => $this->payload['finish_time'],
    ];

    $this->expertHall->update(['is_active' => false]);
    $this->postJson(route('expert.reservation.store', [$this->hall->id]), $body)->assertStatus(422);

    $this->expertHall->update(['is_active' => true]);
    $this->hall->update(['is_active' => false]);
    $this->postJson(route('expert.reservation.store', [$this->hall->id]), $body)->assertNotFound();

    $this->assertDatabaseCount('reservations', 0);
});
