<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expert = Expert::factory()->create(['is_verified' => true]);
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

test('expert should be authenticated to update a service', function () {
    $this->postJson(route('expert.myService.update', [$this->expertService->id]))->assertUnauthorized();
});

test('price and duration are required', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.myService.update', [$this->expertService->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['price', 'duration']);
});

test('an expert can update her own service price and duration', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.myService.update', [$this->expertService->id]), [
        'price' => 300000,
        'duration' => 90,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('expert_service', [
        'id' => $this->expertService->id,
        'price' => 300000,
        'duration' => 90,
    ]);
});

test('an expert cannot update another expert service', function () {
    $stranger = Expert::factory()->create(['is_verified' => true]);
    Sanctum::actingAs($stranger, ['*'], 'expert');

    $response = $this->postJson(route('expert.myService.update', [$this->expertService->id]), [
        'price' => 300000,
        'duration' => 90,
    ]);

    $response->assertForbidden();
});
