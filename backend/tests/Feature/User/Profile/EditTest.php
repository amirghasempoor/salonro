<?php

use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user, ['*'], 'user');
});

test('first name is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('phone number is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('phone_number');
});

test('phone number should match the expected format', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'phone_number' => '12345',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.regex', ['attribute' => 'phone number']),
    ]);
});

test('phone number should be unique among users', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'phone_number' => $this->user->phone_number,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'phone_number' => __('validation.unique', ['attribute' => 'phone number']),
    ]);
});

test('email is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('email');
});

test('email should be a valid email address', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'email' => __('validation.email', ['attribute' => 'email']),
    ]);
});

test('gender is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('gender');
});

test('gender should be 0 or 1', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'gender' => 2,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'gender' => __('validation.in', ['attribute' => 'gender']),
    ]);
});

test('birth date is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('birth_date');
});

test('birth date should be a valid date', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'birth_date' => 'not-a-date',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'birth_date' => __('validation.date', ['attribute' => 'birth date']),
    ]);
});

test('province id is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('province_id');
});

test('province id should exist', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'province_id' => 999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'province_id' => __('validation.exists', ['attribute' => 'province id']),
    ]);
});

test('city id is required', function () {
    $response = $this->postJson(route('user.profile.edit'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('city_id');
});

test('city id should exist', function () {
    $response = $this->postJson(route('user.profile.edit'), [
        'city_id' => 999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'city_id' => __('validation.exists', ['attribute' => 'city id']),
    ]);
});

test('user can edit the profile', function () {
    $province = Province::factory()->create(['name' => 'Tehran']);
    $city = City::factory()->create(['name' => 'Tehran', 'province_id' => $province->id]);

    $response = $this->postJson(route('user.profile.edit'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '09123456789',
        'email' => 'john.doe@example.com',
        'gender' => 1,
        'birth_date' => '2000-01-01',
        'province_id' => $province->id,
        'city_id' => $city->id,
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    $this->assertDatabaseHas('users', [
        'id' => $this->user->id,
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '09123456789',
        'email' => 'john.doe@example.com',
        'gender' => 1,
        'province_id' => $province->id,
        'province_name' => 'Tehran',
        'city_id' => $city->id,
        'city_name' => 'Tehran',
        'kyc_status' => 1,
    ]);
});
