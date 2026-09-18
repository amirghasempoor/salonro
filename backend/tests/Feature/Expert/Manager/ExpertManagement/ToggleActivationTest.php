<?php

use App\Models\Expert;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $this->staff = Expert::factory()->create();
    $this->hall->experts()->attach($this->staff->id, ['joined_at' => now(), 'is_active' => true]);
});

test('manager should be authenticated to toggle a staff activation', function () {
    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]))
        ->assertUnauthorized();
});

test('is active is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('is_active');
});

test('is active should be boolean', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]), [
        'is_active' => 'not-a-boolean',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'is_active' => __('validation.boolean', ['attribute' => 'is active']),
    ]);
});

test('manager can deactivate a staff member', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]), [
        'is_active' => false,
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $this->staff->id,
        'hall_id' => $this->hall->id,
        'is_active' => 0,
    ]);
});

test('manager can reactivate a staff member', function () {
    $this->hall->experts()->updateExistingPivot($this->staff->id, ['is_active' => false]);

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]), [
        'is_active' => true,
    ]);

    $response->assertOk();

    $this->assertDatabaseHas('expert_hall', [
        'expert_id' => $this->staff->id,
        'hall_id' => $this->hall->id,
        'is_active' => 1,
    ]);
});

test('updating a staff does not change their personal info', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $originalFirstName = $this->staff->first_name;

    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]), [
        'is_active' => false,
    ])->assertOk();

    $this->assertDatabaseHas('experts', [
        'id' => $this->staff->id,
        'first_name' => $originalFirstName,
    ]);
});

test('manager cannot toggle activation on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $this->staff->id]), [
        'is_active' => false,
    ])->assertForbidden();
});

test('manager cannot toggle activation for a staff that does not belong to their hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $foreignStaff = Expert::factory()->create();

    $this->postJson(route('expert.staff.toggleActivation', [$this->hall->id, $foreignStaff->id]), [
        'is_active' => false,
    ])->assertForbidden();
});
