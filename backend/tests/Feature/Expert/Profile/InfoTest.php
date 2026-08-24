<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

test('expert should be authenticated to see the info', function () {
    $this->getJson(route('expert.profile.info'))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.profile.info'))->assertUnauthorized();
});

test('expert can see the profile info', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');
    $response = $this->getJson(route('expert.profile.info'));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'first_name',
            'last_name',
            'avatar',
            'phone_number',
            'role',
            'halls',
            'is_verified',
            'bio',
            'is_active',
        ]
    ]);
});
