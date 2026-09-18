<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('phone number is required', function () {
    $response = $this->postJson(route('user.auth.loginWithPassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $response = $this->postJson(route('user.auth.loginWithPassword'), [
        'phone_number' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('password is required', function () {
    $response = $this->postJson(route('user.auth.loginWithPassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('password');
});

test('password should be string', function () {
    $response = $this->postJson(route('user.auth.loginWithPassword'), [
        'password' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.string', ['attribute' => 'password']),
    ]);
});

test('login fails for a phone number that does not exist', function () {
    $response = $this->postJson(route('user.auth.loginWithPassword'), [
        'phone_number' => '09'.fake()->numerify('#########'),
        'password' => 'whatever123',
    ]);

    $response->assertStatus(422);
    $response->assertExactJson([
        'type' => 'logical_exception',
        'message' => __('messages.invalid_credentials'),
    ]);
});

test('user cannot login with the wrong password', function () {
    $user = User::factory()->create([
        'phone_number' => '09'.fake()->numerify('#########'),
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson(route('user.auth.loginWithPassword'), [
        'phone_number' => $user->phone_number,
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
    $response->assertExactJson([
        'type' => 'logical_exception',
        'message' => __('messages.invalid_credentials'),
    ]);
});

test('user can login with password', function () {
    $user = User::factory()->create([
        'phone_number' => '09'.fake()->numerify('#########'),
        'password' => Hash::make('correct-password'),
    ]);

    $response = $this->postJson(route('user.auth.loginWithPassword'), [
        'phone_number' => $user->phone_number,
        'password' => 'correct-password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'token',
        ],
    ]);
});
