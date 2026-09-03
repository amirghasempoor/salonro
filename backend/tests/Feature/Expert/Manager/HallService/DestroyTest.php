<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $owner = Expert::factory()->create();
    $hall = Hall::factory()->create(['owner_id' => $owner->id]);
    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $owner->id,
        'hall_id' => $hall->id,
    ]);
    $this->hallService = HallService::factory()->create([
        'hall_id' => $hall->id,
    ]);
});

test('manager should be authenticated to delete a hall service', function () {
    $this->deleteJson(route('expert.services.destroy', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->deleteJson(route('expert.services.destroy', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertUnauthorized();
});

test('manager can delete a hall service', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->delete(route('expert.services.destroy', [$this->expertHall->hall_id, $this->hallService->id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseMissing('hall_service', [
        'id' => $this->hallService->id,
    ]);
});

test('manager cannot delete a service on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->deleteJson(route('expert.services.destroy', [$this->expertHall->hall_id, $this->hallService->id]))
        ->assertForbidden();
});

test('manager cannot delete a service that belongs to another hall', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');

    $foreignService = HallService::factory()->create();

    $this->deleteJson(route('expert.services.destroy', [$this->expertHall->hall_id, $foreignService->id]))
        ->assertForbidden();
});
