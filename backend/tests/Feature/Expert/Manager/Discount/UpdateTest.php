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
    $this->payload = [
        'type' => 'holiday',
        'title' => 'new title',
        'amount_type' => 'fixed',
        'amount' => 7000,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(2)->toDateString(),
        'is_active' => false,
    ];
});

test('manager should be authenticated to update a discount', function () {
    $this->postJson(route('expert.discount.update', [$this->hall->id, $this->discount->id]))
        ->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->postJson(route('expert.discount.update', [$this->hall->id, $this->discount->id]))
        ->assertUnauthorized();
});

test('is active is required on update', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    unset($this->payload['is_active']);

    $this->postJson(route('expert.discount.update', [$this->hall->id, $this->discount->id]), $this->payload)
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('is_active');
});

test('manager can update a discount', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.discount.update', [$this->hall->id, $this->discount->id]), $this->payload)
        ->assertOk();

    $this->assertDatabaseHas('discounts', [
        'id' => $this->discount->id,
        'hall_id' => $this->hall->id,
        'title' => 'new title',
        'amount_type' => 'fixed',
        'amount' => 7000,
        'is_active' => 0,
    ]);
});

test('manager cannot update a discount on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.discount.update', [$this->hall->id, $this->discount->id]), $this->payload)
        ->assertForbidden();

    $this->assertDatabaseMissing('discounts', ['id' => $this->discount->id, 'title' => 'new title']);
});

test('manager cannot update another hall discount through their own hall', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $foreign = Discount::factory()->create();

    $this->postJson(route('expert.discount.update', [$this->hall->id, $foreign->id]), $this->payload)
        ->assertForbidden();

    $this->assertDatabaseMissing('discounts', ['id' => $foreign->id, 'title' => 'new title']);
});
