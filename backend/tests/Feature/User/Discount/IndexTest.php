<?php

use App\Models\Discount;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
});

test('user should be authenticated to list their discounts', function () {
    $this->getJson(route('user.discount.index'))
        ->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->user, ['*'], 'expert');

    $this->getJson(route('user.discount.index'))
        ->assertUnauthorized();
});

test('it lists only the authenticated user own active manual discounts', function () {
    $otherUser = User::factory()->create();

    $own = Discount::factory()->manual($this->user)->create();
    Discount::factory()->manual($this->user)->create(['is_active' => false]);
    Discount::factory()->manual($otherUser)->create();
    Discount::factory()->holiday()->create();

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.discount.index'));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.id', $own->id);
});
