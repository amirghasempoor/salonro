<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\HallService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
});

test('manager should be authenticated to see the halls services', function () {
    $this->getJson(route('expert.hall.services', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.hall.services', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager can see the halls services', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    HallService::factory()->create([
        'hall_id' => $this->expertHall->hall_id,
    ]);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.hall.services', [$this->expertHall->hall_id]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'sub_cat_name',
            ],
        ],
    ]);
});
