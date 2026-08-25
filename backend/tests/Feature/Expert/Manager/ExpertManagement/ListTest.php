<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
});

test('manager should be authenticated to list the hall staff', function () {
    $this->getJson(route('expert.staff.list', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.staff.list', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager can list the hall staff', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');

    $response = $this->getJson(route('expert.staff.list', [$this->expertHall->hall_id]));

    $response->assertOk();
    $response->assertJsonStructure([
        [
            'avatar',
        ],
    ]);
});
