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
