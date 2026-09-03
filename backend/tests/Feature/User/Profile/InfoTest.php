<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('user should be authenticated to see the info', function () {
    $this->getJson(route('user.profile.info'))->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'expert');

    $this->getJson(route('user.profile.info'))->assertUnauthorized();
});

test('user can see the profile info', function () {
    $user = User::factory()->create([
        'first_name' => 'John',
        'last_name' => 'Doe',
        'phone_number' => '09123456789',
        'kyc_status' => 1,
    ]);
    Sanctum::actingAs($user, ['*'], 'user');

    $response = $this->getJson(route('user.profile.info'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'first_name',
            'last_name',
            'avatar',
            'phone_number',
            'kyc_status',
        ],
    ]);
    $response->assertJsonPath('data.first_name', 'John');
    $response->assertJsonPath('data.last_name', 'Doe');
    $response->assertJsonPath('data.phone_number', '09123456789');
    $response->assertJsonPath('data.kyc_status', 1);
});
