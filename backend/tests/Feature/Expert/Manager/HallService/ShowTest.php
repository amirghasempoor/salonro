<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $owner = Expert::factory()->create();
    $hall = Hall::factory()->create(['owner_id' => $owner->id]);
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $owner->id,
        'hall_id' => $hall->id,
    ]);
    $this->hallService = HallService::factory()->create([
        'hall_id' => $hall->id,
    ]);
});

test('manager should be authenticated to see a hall service', function () {
    $this->getJson(route('expert.services.show', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.services.show', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertUnauthorized();
});

test('manager can see a hall service info', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.services.show', [$this->expertHall->hall_id, $this->hallService->id]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'service_id',
            'description',
            'duration',
            'price',
            'is_active',
            'service' => [
                'id',
            ],
        ],
    ]);
});

test('manager cannot see a service of a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.services.show', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertForbidden();
});

test('manager cannot see a service that belongs to another hall', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');

    $foreignService = HallService::factory()->create();

    $this->getJson(route('expert.services.show', [$this->expertHall->hall_id, $foreignService->id]))
        ->assertForbidden();
});
