<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    $this->expertService = ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->expertHall->id,
    ]);
});

test('expert should be authenticated to see a service', function () {
    $this->getJson(route('expert.myService.show', $this->expertService->id))
        ->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->expert, ['*'], 'user');
    $this->getJson(route('expert.myService.show', $this->expertService->id))
        ->assertUnauthorized();
});

test('an expert can see her own service info', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.myService.show', $this->expertService->id));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'id',
            'service_id',
            'price',
            'duration',
            'service' => ['id'],
        ],
    ]);
});

test('an expert cannot see another expert service', function () {
    $stranger = Expert::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'expert');

    $this->getJson(route('expert.myService.show', $this->expertService->id))
        ->assertForbidden();
});
