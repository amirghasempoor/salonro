<?php

use App\Models\User;
use Laravel\Sanctum\Sanctum;

test('requires authentication', function () {
    $this->postJson(route('user.auth.logout'))->assertUnauthorized();
});

test('an authenticated customer can logout', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['*'], 'user');

    $response = $this->postJson(route('user.auth.logout'));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);
});

test('logout revokes the token so it cannot be reused', function () {
    $user = User::factory()->create();
    $token = $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken;

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('user.auth.logout'))
        ->assertOk();

    $this->app['auth']->forgetGuards();

    $this->withHeader('Authorization', 'Bearer '.$token)
        ->postJson(route('user.auth.logout'))
        ->assertUnauthorized();
});
