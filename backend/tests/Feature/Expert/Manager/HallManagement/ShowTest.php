<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
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
