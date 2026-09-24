<?php

use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
    $this->discount = Discount::factory()->create(['hall_id' => $this->hall->id]);
});

test('manager should be authenticated to see a discount', function () {
    $this->getJson(route('expert.discount.show', [$this->hall->id, $this->discount->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->getJson(route('expert.discount.show', [$this->hall->id, $this->discount->id]))
        ->assertUnauthorized();
});

test('manager can see a discount', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->getJson(route('expert.discount.show', [$this->hall->id, $this->discount->id]))
        ->assertOk()
        ->assertJsonPath('data.id', $this->discount->id)
        ->assertJsonPath('data.hall_id', $this->hall->id);
});

test('manager cannot see a discount on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.discount.show', [$this->hall->id, $this->discount->id]))
        ->assertForbidden();
});

test('manager cannot reach another hall discount through their own hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $foreign = Discount::factory()->create();

    $this->getJson(route('expert.discount.show', [$this->hall->id, $foreign->id]))
        ->assertForbidden();
});
