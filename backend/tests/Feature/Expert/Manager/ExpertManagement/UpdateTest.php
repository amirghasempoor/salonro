<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->expertHall = ExpertHall::factory()->create();
    $this->staff = Expert::query()->find($this->expertHall->expert_id);
});

test('manager should be authenticated to update a staff', function () {
    $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]))
        ->assertUnauthorized();
});

test('first name is required', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('phone number is required', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]), [
        'phone_number' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('phone number should be unique', function () {
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');

    $user = User::factory()->create();

    $response = $this->postJson(route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]), [
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
    $expert = Expert::factory()->create();
    $expert->assignRole('manager');
    Sanctum::actingAs($expert, ['*'], 'expert');

    $data = [
        'first_name' => 'Updated',
        'last_name' => 'Staff',
        'phone_number' => '09'.fake()->numerify('#########'),
    ];

    $response = $this->postJson(
        route('expert.staff.update', [$this->expertHall->hall_id, $this->staff->id]),
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
