<?php

use App\Models\Expert;
use Laravel\Sanctum\Sanctum;

test('phone number is required', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'expert');

    $response = $this->postJson(route('expert.auth.logout'));

    $response->assertOk();
    $response->assertExactJson([
        'message' => __('messages.successful'),
    ]);
});
