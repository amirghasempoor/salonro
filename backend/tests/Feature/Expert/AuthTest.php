<?php

use App\Facades\Sms\Sms;
use App\Models\City;
use App\Models\Expert;
use App\Models\Otp;
use App\Models\Province;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    seedRoles();

    $this->province = Province::factory()->create();
    $this->city = City::factory()->create(['province_id' => $this->province->id]);
    $this->phone = '09123456789';
});

test('expert can register', function () {
    $this->postJson('/expert/auth/register', [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'email' => 'ali@example.com',
        'phone_number' => $this->phone,
        'password' => validPassword(),
        'password_confirmation' => validPassword(),
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
        'role' => 1,
    ])->assertOk()
        ->assertJsonStructure(['data' => ['token', 'role']])
        ->assertJsonPath('data.role', 'expert');

    $this->assertDatabaseHas('experts', ['phone_number' => $this->phone]);
    expect(Expert::firstWhere('phone_number', $this->phone)->hasRole('expert'))->toBeTrue();
});

test('expert registration with role manager assigns manager role', function () {
    $this->postJson('/expert/auth/register', [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'phone_number' => $this->phone,
        'password' => validPassword(),
        'password_confirmation' => validPassword(),
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
        'role' => 2,
    ])->assertOk()
        ->assertJsonPath('data.role', 'manager');
});

test('expert registration fails with an invalid role', function () {
    $this->postJson('/expert/auth/register', [
        'first_name' => 'Ali',
        'last_name' => 'Rezaei',
        'phone_number' => $this->phone,
        'password' => validPassword(),
        'password_confirmation' => validPassword(),
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
        'role' => 3,
    ])->assertStatus(422);
});

test('expert can login with password', function () {
    $expert = Expert::factory()->create([
        'phone_number' => $this->phone,
        'password' => Hash::make(validPassword()),
    ]);
    $expert->assignRole('expert');

    $this->postJson('/expert/auth/login_with_password', [
        'phone_number' => $this->phone,
        'password' => validPassword(),
    ])->assertOk()
        ->assertJsonStructure(['data' => ['token', 'is_verified', 'role']])
        ->assertJsonPath('data.role', 'expert');
});

test('expert login fails with wrong password', function () {
    Expert::factory()->create([
        'phone_number' => $this->phone,
        'password' => Hash::make(validPassword()),
    ]);

    $this->postJson('/expert/auth/login_with_password', [
        'phone_number' => $this->phone,
        'password' => 'wrongpassword1',
    ])->assertStatus(422);
});

test('expert login fails for a non-existent phone number', function () {
    $this->postJson('/expert/auth/login_with_password', [
        'phone_number' => '09111112222',
        'password' => validPassword(),
    ])->assertStatus(422);
});

test('expert can login with otp', function () {
    Sms::shouldReceive('send')->once();

    $this->postJson('/expert/auth/send_otp', ['phone_number' => $this->phone])->assertOk();

    $code = (string) Otp::firstWhere('phone_number', $this->phone)->verification_code;

    $this->postJson('/expert/auth/login_with_otp', [
        'phone_number' => $this->phone,
        'verification_code' => $code,
    ])->assertOk()
        ->assertJsonStructure(['data' => ['token', 'is_verified', 'role']]);

    $this->assertDatabaseHas('experts', ['phone_number' => $this->phone]);
});

test('expert can logout with a valid token', function () {
    $expert = Expert::factory()->create(['password' => Hash::make(validPassword())]);
    $token = $expert->createToken('EXPERT_TOKEN', ['*'])->plainTextToken;

    $this->withToken($token)
        ->postJson('/expert/auth/logout')
        ->assertOk();

    expect($expert->tokens()->count())->toBe(0);
});
