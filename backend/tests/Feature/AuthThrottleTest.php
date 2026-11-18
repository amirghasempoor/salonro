<?php

test('otp requests are limited per phone number', function (string $route) {
    $payload = ['phone_number' => '09120000001'];

    foreach (range(1, 3) as $attempt) {
        expect($this->postJson(route($route), $payload)->status())->not->toBe(429);
    }

    $this->postJson(route($route), $payload)
        ->assertStatus(429)
        ->assertHeader('Retry-After');
})->with(['expert.auth.sendOtp', 'user.auth.sendOtp']);

test('a different phone number is not blocked by another number being limited', function () {
    foreach (range(1, 4) as $attempt) {
        $this->postJson(route('user.auth.sendOtp'), ['phone_number' => '09120000001']);
    }

    expect($this->postJson(route('user.auth.sendOtp'), ['phone_number' => '09120000002'])->status())
        ->not->toBe(429);
});

test('password login attempts are limited per phone number', function (string $route) {
    $payload = ['phone_number' => '09120000001', 'password' => 'wrong-password'];

    foreach (range(1, 5) as $attempt) {
        expect($this->postJson(route($route), $payload)->status())->not->toBe(429);
    }

    $this->postJson(route($route), $payload)->assertStatus(429);
})->with(['expert.auth.loginWithPassword', 'user.auth.loginWithPassword']);

test('otp login attempts are limited per phone number', function (string $route) {
    $payload = ['phone_number' => '09120000001', 'otp' => '000000'];

    foreach (range(1, 5) as $attempt) {
        expect($this->postJson(route($route), $payload)->status())->not->toBe(429);
    }

    $this->postJson(route($route), $payload)->assertStatus(429);
})->with(['expert.auth.loginWithOtp', 'user.auth.loginWithOtp']);
