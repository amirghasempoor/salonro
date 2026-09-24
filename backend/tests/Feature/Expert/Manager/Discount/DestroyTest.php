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

test('manager should be authenticated to delete a discount', function () {
    $this->deleteJson(route('expert.discount.destroy', [$this->hall->id, $this->discount->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->deleteJson(route('expert.discount.destroy', [$this->hall->id, $this->discount->id]))
        ->assertUnauthorized();
});

test('manager can delete a discount', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->deleteJson(route('expert.discount.destroy', [$this->hall->id, $this->discount->id]))
        ->assertOk();

    $this->assertDatabaseMissing('discounts', ['id' => $this->discount->id]);
});

test('manager cannot delete a discount on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->deleteJson(route('expert.discount.destroy', [$this->hall->id, $this->discount->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('discounts', ['id' => $this->discount->id]);
});

test('manager cannot delete another hall discount through their own hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $foreign = Discount::factory()->create();

    $this->deleteJson(route('expert.discount.destroy', [$this->hall->id, $foreign->id]))
        ->assertForbidden();

    $this->assertDatabaseHas('discounts', ['id' => $foreign->id]);
});
