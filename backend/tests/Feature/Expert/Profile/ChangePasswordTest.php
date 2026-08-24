<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
});

test('current password is required', function () {
    $response = $this->postJson(route('expert.profile.changePassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('current_password');
});

test('current password should be string', function () {
    $response = $this->postJson(route('expert.profile.changePassword'), [
        'current_password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'current_password' => __('validation.string', ['attribute' => 'current password']),
    ]);
});

test('current password should be at least 8 characters', function () {
    $response = $this->postJson(route('expert.profile.changePassword'), [
        'current_password' => fake()->password(2, 5),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'current_password' => __('validation.min.string', ['attribute' => 'current password', 'min' => 8]),
    ]);
});

test('new password is required', function () {
    $response = $this->postJson(route('expert.profile.changePassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('new_password');
});

test('new password should be string', function () {
    $response = $this->postJson(route('expert.profile.changePassword'), [
        'new_password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'new_password' => __('validation.string', ['attribute' => 'new password']),
    ]);
});

test('new password should be at least 8 characters', function () {
    $response = $this->postJson(route('expert.profile.changePassword'), [
        'new_password' => fake()->password(2, 5),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'new_password' => __('validation.min.string', ['attribute' => 'new password', 'min' => 8]),
    ]);
});

test('expert can not change the password with wrong credentials', function () {
    $response = $this->postJson(route('expert.profile.changePassword'), [
        'current_password' => fake()->lexify('????123@'),
        'new_password' => fake()->lexify('pass123@'),
        'new_password_confirmation' => fake()->lexify('pass123@'),
    ]);

    $response->assertStatus(422);
    $response->assertExactJson([
        'type' => 'logical_exception',
        'message' => __('messages.incorrect_current_password'),
    ]);
});


test('expert can change the password', function () {
    $response = $this->postJson(route('expert.profile.changePassword'), [
        'current_password' => 'pass123@',
        'new_password' => fake()->lexify('pass@123'),
        'new_password_confirmation' => fake()->lexify('pass@123'),
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);
});
