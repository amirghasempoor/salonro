<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
});

test('first name is required', function () {
    $response = $this->postJson(route('expert.profile.update'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $response = $this->postJson(route('expert.profile.update'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('avatar should be a valid image type', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'avatar' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.image', ['attribute' => 'avatar']),
    ]);
});

test('bio should be string', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'bio' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'bio' => __('validation.string', ['attribute' => 'bio']),
    ]);
});

test('bio should not exceed 255 characters', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'bio' => fake()->text(500),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'bio' => __('validation.max.string', ['attribute' => 'bio', 'max' => 255]),
    ]);
});

test('is active is required', function () {
    $response = $this->postJson(route('expert.profile.update'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('is_active');
});

test('is active  should be boolean', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'is_active' => fake()->numerify('##'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'is_active' => __('validation.boolean', ['attribute' => 'is active']),
    ]);
});

test('expert can update the account', function () {
    $response = $this->postJson(route('expert.profile.update'), [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'bio' => fake()->text(100),
        'is_active' => true,
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);
});
