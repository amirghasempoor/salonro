<?php

use App\Facades\Otp\OtpFacade;

test('phone number is required', function () {
    $response = $this->postJson(route('expert.auth.sendOtp'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $response = $this->postJson(route('expert.auth.sendOtp'), [
        'phone_number' => fake()->numberBetween(1, 9),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('phone number should match regex', function () {
    $response = $this->postJson(route('expert.auth.sendOtp'), [
        'phone_number' => fake()->numerify('3###'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.regex', ['attribute' => 'phone number']),
    ]);
});

test('otp will send successfully', function () {
    OtpFacade::expects('generate')->once()->andReturn(true);

    $response = $this->postJson(route('expert.auth.sendOtp'), [
        'phone_number' => fake()->numerify('09#########'),
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);
});
