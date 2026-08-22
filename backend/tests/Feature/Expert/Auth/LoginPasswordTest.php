<?php

use App\Facades\Otp\OtpFacade;
use App\Models\Expert;

test('phone number is required', function () {
    $response = $this->postJson(route('expert.auth.loginWithPassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $response = $this->postJson(route('expert.auth.loginWithPassword'), [
        'phone_number' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('password is required', function () {
    $response = $this->postJson(route('expert.auth.loginWithPassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('password');
});

test('password should be string', function () {
    $response = $this->postJson(route('expert.auth.loginWithPassword'), [
        'password' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.string', ['attribute' => 'password']),
    ]);
});

test('expert can not login with wrong password', function () {
    $expert = Expert::factory()->create();

    $response = $this->postJson(route('expert.auth.loginWithPassword'), [
        'phone_number' => $expert->phone_number,
        'password' => fake()->numerify('####'),
    ]);

    $response->assertStatus(422);
    $response->assertExactJson([
        'type' => 'logical_exception',
        'message' => __('messages.invalid_credential'),
    ]);
});

test('expert can login with password', function () {
    $expert = Expert::factory()->create();

    $response = $this->postJson(route('expert.auth.loginWithPassword'), [
        'phone_number' => $expert->phone_number,
        'password' => 'password',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'token',
            'is_verified',
            'role',
        ]
    ]);
});
