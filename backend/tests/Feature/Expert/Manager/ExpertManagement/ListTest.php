<?php

use App\Models\Expert;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
});

test('manager should be authenticated to list the hall staff', function () {
    $this->getJson(route('expert.staff.list', [$this->hall->id]))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.staff.list', [$this->hall->id]))->assertUnauthorized();
});

test('manager can list the hall staff', function () {
    $staff = Expert::factory()->create();
    $this->hall->experts()->attach($staff->id, ['joined_at' => now()]);

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->getJson(route('expert.staff.list', [$this->hall->id]));

    $response->assertOk();
    $response->assertJsonStructure([
        [
            'avatar',
        ],
    ]);
});

test('manager cannot list the staff of a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.staff.list', [$this->hall->id]))->assertForbidden();
});
