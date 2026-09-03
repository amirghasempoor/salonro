<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create([
        'password' => Hash::make('pass123@word'),
    ]);
    Sanctum::actingAs($this->user, ['*'], 'user');
});

test('current password is required', function () {
    $response = $this->postJson(route('user.profile.changePassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('current_password');
});

test('current password should be string', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'current_password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'current_password' => __('validation.string', ['attribute' => 'current password']),
    ]);
});

test('current password should be at least 8 characters', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'current_password' => 'a1b2',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'current_password' => __('validation.min.string', ['attribute' => 'current password', 'min' => 8]),
    ]);
});

test('current password should contain both letters and digits', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'current_password' => 'onlyletters',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('current_password');
});

test('new password is required', function () {
    $response = $this->postJson(route('user.profile.changePassword'));

    $response->assertStatus(422);
    $response->assertJsonValidationErrorFor('new_password');
});

test('new password should be string', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'new_password' => fake()->numberBetween(1, 100),
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'new_password' => __('validation.string', ['attribute' => 'new password']),
    ]);
});

test('new password should be at least 8 characters', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'new_password' => 'a1b2',
    ]);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors([
        'new_password' => __('validation.min.string', ['attribute' => 'new password', 'min' => 8]),
    ]);
});

test('user can not change the password with a wrong current password', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'current_password' => 'wrong123pass',
        'new_password' => 'newpass123',
    ]);

    $response->assertStatus(422);
    $response->assertExactJson([
        'type' => 'logical_exception',
        'message' => __('messages.incorrect_current_password'),
    ]);
});

test('user can change the password', function () {
    $response = $this->postJson(route('user.profile.changePassword'), [
        'current_password' => 'pass123@word',
        'new_password' => 'newpass123',
    ]);

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);

    expect(Hash::check('newpass123', $this->user->fresh()->password))->toBeTrue();
});
