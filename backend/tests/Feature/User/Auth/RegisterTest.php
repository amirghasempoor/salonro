<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('first name is required to register', function () {
    $response = $this->postJson(route('user.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('first name should not exceed 255 characters', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'first_name' => fake()->text(600),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.max.string', ['attribute' => 'first name', 'max' => 255]),
    ]);
});

// ######################################################################################
test('last name is required to register', function () {
    $response = $this->postJson(route('user.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('last name should not exceed 255 characters', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'last_name' => fake()->text(600),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.max.string', ['attribute' => 'last name', 'max' => 255]),
    ]);
});

// ######################################################################################
test('password is required to register', function () {
    $response = $this->postJson(route('user.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('password');
});

test('password should be string', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.string', ['attribute' => 'password']),
    ]);
});

test('password should be at least 8 characters', function () {
    $shortPassword = fake()->password(2, 5);

    $response = $this->postJson(route('user.auth.register'), [
        'password' => $shortPassword,
        'password_confirmation' => $shortPassword,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.min.string', ['attribute' => 'password', 'min' => 8]),
    ]);
});

test('password must be confirmed', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.confirmed', ['attribute' => 'password']),
    ]);
});

test('password confirmation must match', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'password' => 'password123',
        'password_confirmation' => 'somethingElse123',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.confirmed', ['attribute' => 'password']),
    ]);
});

// ######################################################################################
test('phone number is required to register', function () {
    $response = $this->postJson(route('user.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'phone_number' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('phone number should not exceed 255 characters', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'phone_number' => fake()->numerify(str_repeat('#', 260)),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.max.string', ['attribute' => 'phone number', 'max' => 255]),
    ]);
});

test('phone number should be unique in users table', function () {
    User::factory()->create([
        'phone_number' => '09134844955',
    ]);

    $response = $this->postJson(route('user.auth.register'), [
        'phone_number' => '09134844955',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.unique', ['attribute' => 'phone number']),
    ]);
});

// ######################################################################################
test('customer can register', function () {
    $response = $this->postJson(route('user.auth.register'), [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'phone_number' => fake()->numerify('09#########'),
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'token',
        ],
    ]);

    expect(User::query()->count())->toBe(1);

    $user = User::query()->first();
    expect(Hash::check('password123', $user->password))->toBeTrue();
});
