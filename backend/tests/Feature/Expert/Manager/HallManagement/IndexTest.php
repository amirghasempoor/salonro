<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
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

test('a hall is marked manager when the expert owns it and expert when she only works there', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');

    $ownHall = Hall::factory()->create(['owner_id' => $expert->id]);
    ExpertHall::factory()->create(['expert_id' => $expert->id, 'hall_id' => $ownHall->id]);

    $otherHall = Hall::factory()->create();
    ExpertHall::factory()->create(['expert_id' => $expert->id, 'hall_id' => $otherHall->id]);

    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.hall.index', [
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $roles = collect($response->json('data'))->pluck('role', 'id');

    expect($roles[$ownHall->id])->toBe('manager')
        ->and($roles[$otherHall->id])->toBe('expert');
});
