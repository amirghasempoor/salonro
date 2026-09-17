<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $this->staff = Expert::factory()->create();
    $this->hall->experts()->attach($this->staff->id, ['joined_at' => now()]);
});

test('manager should be authenticated to update a staff', function () {
    $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]))
        ->assertUnauthorized();
});

test('first name is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('phone number is required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]), [
        'phone_number' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('phone number should be unique', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $user = User::factory()->create();

    $response = $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]), [
        'first_name' => fake()->firstName(),
        'last_name' => fake()->lastName(),
        'phone_number' => $user->phone_number,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.unique', ['attribute' => 'phone number']),
    ]);
});

test('manager can update a staff', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $data = [
        'first_name' => 'Updated',
        'last_name' => 'Staff',
        'phone_number' => '09'.fake()->numerify('#########'),
    ];

    $response = $this->postJson(
        route('expert.staff.update', [$this->hall->id, $this->staff->id]),
        $data
    );

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('experts', [
        'id' => $this->staff->id,
        'first_name' => 'Updated',
        'last_name' => 'Staff',
        'phone_number' => $data['phone_number'],
    ]);
});

test('manager cannot update a staff on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.staff.update', [$this->hall->id, $this->staff->id]))
        ->assertForbidden();
});

test('manager cannot update a staff that does not belong to their hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $foreignStaff = Expert::factory()->create();

    $this->postJson(route('expert.staff.update', [$this->hall->id, $foreignStaff->id]))
        ->assertForbidden();
});
