<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
});

test('manager should be authenticated to see the halls', function () {
    $this->getJson(route('expert.hall.index'))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.hall.index'))->assertUnauthorized();
});

test('manager can see the halls info', function () {
    $expert = Expert::factory()->create();
    ExpertHall::factory()->create(['expert_id' => $expert->id]);
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.hall.index', [
        'start' => 0,
        'size' => 5,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'owner_name',
                'name',
                'lat',
                'lng',
                'address',
            ],
        ],
        'meta' => [],
    ]);
});
