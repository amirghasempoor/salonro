<?php

use App\Models\Expert;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
});

test('manager should be authenticated to see the hall staff', function () {
    $this->getJson(route('expert.staff.index', [$this->hall->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.staff.index', [$this->hall->id]))->assertUnauthorized();
});

test('manager can see the hall staff', function () {
    $staff = Expert::factory()->count(2)->create();
    $this->hall->experts()->attach($staff->pluck('id')->all(), ['joined_at' => now()]);

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->getJson(route('expert.staff.index', [
        'hall' => $this->hall->id,
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

test('manager cannot see the staff of a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.staff.index', [$this->hall->id]))->assertForbidden();
});
