<?php

use App\Facades\Sms\Sms;
use App\Models\Otp;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->phone = '09123456789';
});

test('user can register with valid data', function () {
    $this->postJson('/auth/register', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => $this->phone,
        'password' => validPassword(),
        'password_confirmation' => validPassword(),
    ])->assertOk();

    $this->assertDatabaseHas('users', ['phone_number' => $this->phone]);
    $this->assertAuthenticatedAs(User::firstWhere('phone_number', $this->phone), 'web');
});

test('registration fails when passwords do not match', function () {
    $this->postJson('/auth/register', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => $this->phone,
        'password' => validPassword(),
        'password_confirmation' => 'different123',
    ])->assertStatus(422);
});

test('registration fails with a duplicate phone number', function () {
    User::factory()->create(['phone_number' => $this->phone]);

    $this->postJson('/auth/register', [
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => $this->phone,
        'password' => validPassword(),
        'password_confirmation' => validPassword(),
    ])->assertStatus(422);
});

test('user can login with password', function () {
    $user = User::factory()->create([
        'phone_number' => $this->phone,
        'password' => Hash::make(validPassword()),
    ]);

    $this->postJson('/auth/login_with_password', [
        'phone_number' => $this->phone,
        'password' => validPassword(),
    ])->assertOk();

    $this->assertAuthenticatedAs($user, 'web');
});

test('login with wrong password fails', function () {
    User::factory()->create([
        'phone_number' => $this->phone,
        'password' => Hash::make(validPassword()),
    ]);

    $this->postJson('/auth/login_with_password', [
        'phone_number' => $this->phone,
        'password' => 'wrongpassword1',
    ])->assertStatus(422);

    $this->assertGuest('web');
});

test('user can login with otp', function () {
    User::factory()->create(['phone_number' => $this->phone]);

    Sms::shouldReceive('send')->once();

    $this->postJson('/auth/send_otp', ['phone_number' => $this->phone])->assertOk();

    $code = (string) Otp::firstWhere('phone_number', $this->phone)->verification_code;

    $this->postJson('/auth/login_with_otp', [
        'phone_number' => $this->phone,
        'verification_code' => $code,
    ])->assertOk();

    $this->assertAuthenticatedAs(User::firstWhere('phone_number', $this->phone), 'web');
});

test('login with wrong otp fails', function () {
    User::factory()->create(['phone_number' => $this->phone]);

    Sms::shouldReceive('send')->once();

    $this->postJson('/auth/send_otp', ['phone_number' => $this->phone])->assertOk();

    $this->postJson('/auth/login_with_otp', [
        'phone_number' => $this->phone,
        'verification_code' => '0000',
    ])->assertStatus(422);

    $this->assertGuest('web');
});

test('authenticated user can logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/auth/logout')
        ->assertOk();

    $this->assertGuest('web');
});

test('guest cannot logout', function () {
    $this->postJson('/auth/logout')->assertStatus(401);
});
