<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\HallService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
    $this->hallService = HallService::factory()->create([
        'hall_id' => $this->expertHall->hall_id,
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
