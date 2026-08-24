<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
});

test('manager should be authenticated to delete a hall', function () {
    $this->deleteJson(route('expert.hall.destroy', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->deleteJson(route('expert.hall.destroy', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager can delete a hall', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->delete(route('expert.hall.destroy', [$this->expertHall->hall_id]));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful')
    ]);
});
