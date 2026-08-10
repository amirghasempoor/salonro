<?php

use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->province = Province::factory()->create();
    $this->city = City::factory()->create(['province_id' => $this->province->id]);
    $this->user = User::factory()->create(['password' => Hash::make(validPassword())]);
    $this->actingAs($this->user);
});

test('user can fetch profile info', function () {
    $this->getJson('/profile/info')
        ->assertOk()
        ->assertJsonPath('data.first_name', $this->user->first_name)
        ->assertJsonPath('data.phone_number', $this->user->phone_number);
});

test('user can edit profile', function () {
    $this->postJson('/profile/edit', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'phone_number' => '09129998877',
        'email' => 'new@example.com',
        'gender' => 1,
        'birth_date' => '1995-05-05',
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
    ])->assertOk();

    $this->user->refresh();

    expect($this->user->first_name)->toBe('New')
        ->and($this->user->phone_number)->toBe('09129998877')
        ->and($this->user->province_name)->toBe($this->province->name)
        ->and($this->user->city_name)->toBe($this->city->name)
        ->and($this->user->kyc_status)->toBe(1);
});

test('profile edit fails with an invalid phone number', function () {
    $this->postJson('/profile/edit', [
        'first_name' => 'New',
        'last_name' => 'Name',
        'phone_number' => '12345',
        'email' => 'new@example.com',
        'gender' => 1,
        'birth_date' => '1995-05-05',
        'province_id' => $this->province->id,
        'city_id' => $this->city->id,
    ])->assertStatus(422);
});

test('user can change password', function () {
    $this->postJson('/profile/change_password', [
        'current_password' => validPassword(),
        'new_password' => 'newpassword123',
    ])->assertOk();

    $this->user->refresh();

    expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
});

test('change password fails with a wrong current password', function () {
    $this->postJson('/profile/change_password', [
        'current_password' => 'wrongpassword1',
        'new_password' => 'newpassword123',
    ])->assertStatus(422);
});
