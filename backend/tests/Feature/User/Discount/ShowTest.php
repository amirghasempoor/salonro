<?php

use App\Models\Discount;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user should be authenticated to view a discount', function () {
    $discount = Discount::factory()->manual($this->user)->create();

    $this->getJson(route('user.discount.show', $discount))
        ->assertUnauthorized();
});

test('user can view their own manual discount', function () {
    $discount = Discount::factory()->manual($this->user)->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.discount.show', $discount));

    $response->assertOk();
    $response->assertJsonPath('data.id', $discount->id);
});

test('user cannot view another user manual discount', function () {
    $otherUser = User::factory()->create();
    $discount = Discount::factory()->manual($otherUser)->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.discount.show', $discount))
        ->assertForbidden();
});

test('user can view an active public holiday discount', function () {
    $discount = Discount::factory()->holiday()->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.discount.show', $discount));

    $response->assertOk();
    $response->assertJsonPath('data.id', $discount->id);
});

test('user cannot view an inactive holiday discount', function () {
    $discount = Discount::factory()->holiday()->create(['is_active' => false]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.discount.show', $discount))
        ->assertForbidden();
});
