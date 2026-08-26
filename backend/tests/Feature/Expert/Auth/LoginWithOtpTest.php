<?php

use App\Facades\Otp\OtpFacade;

test('verification code is required', function () {
    $response = $this->postJson(route('expert.auth.loginWithOtp'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('verification_code');
});

test('verification code should be string', function () {
    $response = $this->postJson(route('expert.auth.loginWithOtp'), [
        'verification_code' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'verification_code' => __('validation.string', ['attribute' => 'verification code']),
    ]);
});

test('phone number is required', function () {
    $response = $this->postJson(route('expert.auth.loginWithOtp'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $response = $this->postJson(route('expert.auth.loginWithOtp'), [
        'phone_number' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('otp code and phone number should be valid and paired', function () {
    OtpFacade::expects('verify')->once()->andReturn(false);

    $response = $this->postJson(route('expert.auth.loginWithOtp'), [
        'phone_number' => '09'.fake()->numerify('#########'),
        'verification_code' => fake()->numerify('####'),
    ]);

    $response->assertStatus(422);
    $response->assertExactJson([
        'type' => 'logical_exception',
        'message' => __('messages.incorrect_otp'),
    ]);
});

test('expert can login with otp', function () {
    OtpFacade::expects('verify')->once()->andReturn(true);
    OtpFacade::expects('deactivate')->once()->andReturn(true);

    $response = $this->postJson(route('expert.auth.loginWithOtp'), [
        'phone_number' => '09'.fake()->numerify('#########'),
        'verification_code' => fake()->numerify('####'),
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'token',
            'is_verified',
            'role',
        ],
    ]);
});
