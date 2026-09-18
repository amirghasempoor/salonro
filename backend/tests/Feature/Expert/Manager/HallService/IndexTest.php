<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $owner = Expert::factory()->create();
    $hall = Hall::factory()->create(['owner_id' => $owner->id]);
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $owner->id,
        'hall_id' => $hall->id,
    ]);
});

test('manager should be authenticated to see the halls services', function () {
    $this->getJson(route('expert.services.index', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.services.index', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager can see the halls services', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    HallService::factory()->count(2)->create(['hall_id' => $this->expertHall->hall_id]);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.services.index', [
        'hall' => $this->expertHall->hall_id,
        'start' => 0,
        'size' => 5,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'service_id',
                'description',
                'duration',
                'price',
                'is_active',
            ],
        ],
        'meta' => [
            'totalRowCount',
        ],
    ]);
});

test('manager cannot see the services of a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.services.index', [$this->expertHall->hall_id]))
        ->assertForbidden();
});
