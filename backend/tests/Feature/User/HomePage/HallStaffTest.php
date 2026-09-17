<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Service;

beforeEach(function () {
    $this->hall = Hall::factory()->create();
    $this->serviceA = Service::factory()->create();
    $this->serviceB = Service::factory()->create();

    $this->expertWithA = Expert::factory()->create();
    $expertHallA = ExpertHall::factory()->create([
        'expert_id' => $this->expertWithA->id,
        'hall_id' => $this->hall->id,
    ]);
    $expertHallA->services()->attach($this->serviceA->id);

    $this->expertWithB = Expert::factory()->create();
    $expertHallB = ExpertHall::factory()->create([
        'expert_id' => $this->expertWithB->id,
        'hall_id' => $this->hall->id,
    ]);
    $expertHallB->services()->attach($this->serviceB->id);
});

test('the endpoint is public and requires no authentication', function () {
    $this->getJson(route('user.home.hallStaff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id],
    ]))->assertOk();
});

test('it requires service_ids', function () {
    $this->getJson(route('user.home.hallStaff', $this->hall->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids']);
});

test('it validates service_ids', function () {
    $this->getJson(route('user.home.hallStaff', [$this->hall->id, 'service_ids' => ['not-an-id']]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['service_ids.0']);
});

test('it returns only staff offering the requested service', function () {
    $response = $this->getJson(route('user.home.hallStaff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id],
    ]));

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1);
    expect($data[0]['id'])->toBe($this->expertWithA->id);
});

test('it returns staff matching any of several requested services', function () {
    $response = $this->getJson(route('user.home.hallStaff', [
        $this->hall->id,
        'service_ids' => [$this->serviceA->id, $this->serviceB->id],
    ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});
