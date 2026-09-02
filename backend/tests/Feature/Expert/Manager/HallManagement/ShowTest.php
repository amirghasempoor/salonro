<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $owner = Expert::factory()->create();
    $hall = Hall::factory()->create(['owner_id' => $owner->id]);
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $owner->id,
        'hall_id' => $hall->id,
    ]);
});

test('manager should be authenticated to see the halls info', function () {
    $this->getJson(route('expert.hall.show', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.hall.show', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager can see the halls info', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.hall.show', [$this->expertHall->hall_id]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'owner_name',
            'name',
            'lat',
            'lng',
            'address',
        ],
    ]);
});

test('manager cannot see a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.hall.show', [$this->expertHall->hall_id]))
        ->assertForbidden();
});
