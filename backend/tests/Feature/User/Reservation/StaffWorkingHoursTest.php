<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\User;
use App\Models\WorkingHour;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'sat',
        'from' => '09:00:00',
        'to' => '18:00:00',
    ]);
});

test('the user should be authenticated to see a staff working hours', function () {
    $this->getJson(route('user.reservation.staffWorkingHours', [$this->hall->id, $this->expert->id]))
        ->assertUnauthorized();
});

test('the user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->user, ['*'], 'expert');

    $this->getJson(route('user.reservation.staffWorkingHours', [$this->hall->id, $this->expert->id]))
        ->assertUnauthorized();
});

test('it returns the chosen staff member working hours at the hall', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staffWorkingHours', [$this->hall->id, $this->expert->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.day', 'sat');
    $response->assertJsonPath('data.0.from', '09:00:00');
    $response->assertJsonPath('data.0.to', '18:00:00');
});

test('it only returns that staff member hours at the requested hall, not at another hall', function () {
    $otherHall = Hall::factory()->create();
    $otherExpertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $otherHall->id,
    ]);
    WorkingHour::factory()->create([
        'hourable_id' => $otherExpertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'sun',
        'from' => '10:00:00',
        'to' => '14:00:00',
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staffWorkingHours', [$this->hall->id, $this->expert->id]));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.day', 'sat');
});

test('it returns an empty list for a staff member not assigned to the hall', function () {
    $strangerExpert = Expert::factory()->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffWorkingHours', [$this->hall->id, $strangerExpert->id]))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});
