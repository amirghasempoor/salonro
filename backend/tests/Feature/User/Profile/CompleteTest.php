<?php

use App\Models\City;
use App\Models\Expert;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create([
        'first_name' => null,
        'last_name' => null,
        'kyc_status' => 0,
    ]);
    Sanctum::actingAs($this->user, ['*'], 'user');
});

test('user should be authenticated to complete the profile', function () {
    $this->app['auth']->forgetGuards();
    Sanctum::actingAs(User::factory()->create(), ['*'], 'expert');

    $this->postJson(route('user.profile.complete'))->assertUnauthorized();
});

test('first name is required', function () {
    $response = $this->postJson(route('user.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('first_name');
});

test('first name should be string', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'first_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'first_name' => __('validation.string', ['attribute' => 'first name']),
    ]);
});

test('last name is required', function () {
    $response = $this->postJson(route('user.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('last_name');
});

test('last name should be string', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'last_name' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'last_name' => __('validation.string', ['attribute' => 'last name']),
    ]);
});

test('email should be a valid email address', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'email' => 'not-an-email',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'email' => __('validation.email', ['attribute' => 'email']),
    ]);
});

test('email should be unique among experts', function () {
    Expert::factory()->create(['email' => 'taken@example.com']);

    $response = $this->postJson(route('user.profile.complete'), [
        'email' => 'taken@example.com',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'email' => __('validation.unique', ['attribute' => 'email']),
    ]);
});

test('gender should be 0 or 1', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'gender' => 2,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'gender' => __('validation.in', ['attribute' => 'gender']),
    ]);
});

test('birth date should be a valid date', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'birth_date' => 'not-a-date',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'birth_date' => __('validation.date', ['attribute' => 'birth date']),
    ]);
});

test('province id is required', function () {
    $response = $this->postJson(route('user.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('province_id');
});

test('province id should exist', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'province_id' => 999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'province_id' => __('validation.exists', ['attribute' => 'province id']),
    ]);
});

test('city id is required', function () {
    $response = $this->postJson(route('user.profile.complete'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('city_id');
});

test('city id should exist', function () {
    $response = $this->postJson(route('user.profile.complete'), [
        'city_id' => 999,
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'city_id' => __('validation.exists', ['attribute' => 'city id']),
    ]);
});

test('user can complete the profile', function () {
    $province = Province::factory()->create(['name' => 'Tehran']);
    $city = City::factory()->create(['name' => 'Tehran', 'province_id' => $province->id]);

    $response = $this->postJson(route('user.profile.complete'), [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'email' => 'john.doe@example.com',
        'gender' => 1,
        'birth_date' => '2000-01-01',
        'password' => 'pass123@word',
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
        'email' => 'john.doe@example.com',
        'gender' => 1,
        'kyc_status' => 1,
        'province_id' => $province->id,
        'province_name' => 'Tehran',
        'city_id' => $city->id,
        'city_name' => 'Tehran',
    ]);

    expect(Hash::check('pass123@word', $this->user->fresh()->password))->toBeTrue();
});
