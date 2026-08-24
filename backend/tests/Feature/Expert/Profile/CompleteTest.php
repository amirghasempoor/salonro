<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
});

test('first name is required', function () {
    $response = $this->postJson(route('expert.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $response = $this->postJson(route('expert.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('avatar should be a valid image type', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'avatar' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.image', ['attribute' => 'avatar']),
    ]);
});

test('bio should be string', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'bio' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'bio' => __('validation.string', ['attribute' => 'bio']),
    ]);
});

test('bio should not exceed 255 characters', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'bio' => fake()->text(500),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'bio' => __('validation.max.string', ['attribute' => 'bio', 'max' => 255]),
    ]);
});

test('password is required', function () {
    $response = $this->postJson(route('expert.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('password');
});

test('password should be string', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.string', ['attribute' => 'password']),
    ]);
});

test('password should be at least 8 characters', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'password' => fake()->password(2, 5),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.min.string', ['attribute' => 'password', 'min' => 8]),
    ]);
});

test('expert can complete the profile', function () {
    $response = $this->postJson(route('expert.profile.complete'), [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'bio' => fake()->text(100),
        'password' => 'pass123@',
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);
});
