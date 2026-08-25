<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->expertHall = ExpertHall::factory()->create();
});

test('manager should be authenticated to see the hall staff', function () {
    $this->getJson(route('expert.staff.index', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.staff.index', [$this->expertHall->hall_id]))->assertUnauthorized();
});

test('manager can see the hall staff', function () {
    $expert = Expert::query()->find($this->expertHall->expert_id);

    $staff = Expert::factory()->count(2)->create();
    $this->expertHall->hall->experts()->attach($staff->pluck('id')->all(), ['joined_at' => now()]);

    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.staff.index', [
        'hall' => $this->expertHall->hall_id,
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'id',
                'first_name',
                'last_name',
                'phone_number',
                'avatar',
            ],
        ],
        'meta' => [
            'totalRowCount',
        ],
    ]);
});
