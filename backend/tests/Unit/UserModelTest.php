<?php

use App\Models\User;

test('a user never serializes its password hash or remember token', function () {
    $user = (new User)->forceFill([
        'first_name' => 'Sara',
        'password' => '$2y$12$abcdefghijklmnopqrstuuABCDEFGHIJKLMNOPQRSTUVWXYZ012345',
        'remember_token' => 'abc',
    ]);

    expect($user->toArray())->not->toHaveKeys(['password', 'remember_token'])
        ->and($user->toJson())->not->toContain('password');
});
