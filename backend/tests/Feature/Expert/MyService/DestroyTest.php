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

test('expert should be authenticated to delete a service', function () {
    $this->deleteJson(route('expert.myService.destroy', [$this->expertService->id]))->assertUnauthorized();
});

test('an expert can stop offering her own service', function () {
    Sanctum::actingAs($this->expert, ['*'], 'expert');
    $response = $this->deleteJson(route('expert.myService.destroy', [$this->expertService->id]));

    $response->assertOk();
    $this->assertDatabaseMissing('expert_service', ['id' => $this->expertService->id]);
});

test('an expert cannot delete another expert service', function () {
    $stranger = Expert::factory()->create(['is_verified' => true]);
    Sanctum::actingAs($stranger, ['*'], 'expert');

    $response = $this->deleteJson(route('expert.myService.destroy', [$this->expertService->id]));

    $response->assertForbidden();
    $this->assertDatabaseHas('expert_service', ['id' => $this->expertService->id]);
});
