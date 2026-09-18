<?php

use App\Models\City;
use App\Models\Expert;
use App\Models\Province;
use App\Models\Role;

test('first name is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('first name should should not exceed 255 characters', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'first_name' => fake()->text(600),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.max.string', ['attribute' => 'first name', 'max' => 255]),
    ]);
});

// ######################################################################################
test('last name is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('last name should should not exceed 255 characters', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'last_name' => fake()->text(600),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.max.string', ['attribute' => 'last name', 'max' => 255]),
    ]);
});

// ##########################################################################################
test('password is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('password');
});

test('password should be string', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.string', ['attribute' => 'password']),
    ]);
});

test('password should be at least 8 characters', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'password' => fake()->password(2, 5),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'password' => __('validation.min.string', ['attribute' => 'password', 'min' => 8]),
    ]);
});
// #####################################################################################################
test('phone number is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should be string', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'phone_number' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.string', ['attribute' => 'phone number']),
    ]);
});

test('phone number should not exceed 11 character', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'phone_number' => fake()->numerify('#############'),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.max.string', ['attribute' => 'phone number', 'max' => 11]),
    ]);
});

test('phone number should be unique in experts table', function () {
    Expert::factory()->create([
        'phone_number' => '09134844955',
    ]);
    $response = $this->postJson(route('expert.auth.register'), [
        'phone_number' => '09134844955',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.unique', ['attribute' => 'phone number']),
    ]);
});
// ######################################################################################################
test('email should be string', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'email' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'email' => __('validation.string', ['attribute' => 'email']),
    ]);
});

test('email should be in a valid form', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'email' => fake()->lexify(),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'email' => __('validation.email', ['attribute' => 'email']),
    ]);
});

test('email should be unique in experts table', function () {
    Expert::factory()->create([
        'email' => 'amir@amir.com',
    ]);
    $response = $this->postJson(route('expert.auth.register'), [
        'email' => 'amir@amir.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'email' => __('validation.unique', ['attribute' => 'email']),
    ]);
});
// ##############################################################################################
test('province id is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('province_id');
});

test('province id should be existed in provinces table', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'province_id' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'province_id' => __('validation.exists', ['attribute' => 'province id']),
    ]);
});
// ######################################################################################################
test('city id is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('city_id');
});

test('city id should be existed in citys table', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'city_id' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'city_id' => __('validation.exists', ['attribute' => 'city id']),
    ]);
});
// ##########################################################################################################
test('avatar should be a valid image type', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'avatar' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'avatar' => __('validation.image', ['attribute' => 'avatar']),
    ]);
});
// ############################################################################################################
test('role is required to register', function () {
    $response = $this->postJson(route('expert.auth.register'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('role');
});

test('role should be 1 or 2', function () {
    $response = $this->postJson(route('expert.auth.register'), [
        'role' => fake()->numberBetween(5, 10),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'role' => __('validation.in', ['attribute' => 'role']),
    ]);
});
// ##############################################################################################################
test('expert can be registered', function () {
    Role::factory()->create([
        'name' => 'expert',
    ]);
    $province = Province::factory()->create();
    $city = City::factory()->create();
    $response = $this->postJson(route('expert.auth.register'), [
        'first_name' => fake()->firstName,
        'last_name' => fake()->lastName,
        'phone_number' => fake()->numerify('0##########'),
        'password' => fake()->password(8),
        'email' => fake()->email,
        'province_id' => $province->id,
        'city_id' => $city->id,
        'role' => 1,
    ]);

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'role',
            'token',
        ],
    ]);
});
