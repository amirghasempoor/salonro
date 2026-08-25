<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
    $this->staff = Expert::query()->find($this->expertHall->expert_id);
});

test('manager should be authenticated to see a staff', function () {
    $this->getJson(route('expert.staff.show', [$this->staff->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.staff.show', [$this->staff->id]))->assertUnauthorized();
});

test('manager can see a staff details', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');

    $response = $this->getJson(route('expert.staff.show', [
        'expert' => $this->staff->id,
        'hall_id' => $this->expertHall->hall_id,
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'first_name',
            'last_name',
            'avatar',
            'phone_number',
            'working_hours',
            'portfolio',
        ],
    ]);
});
