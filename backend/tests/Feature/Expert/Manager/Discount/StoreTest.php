<?php

use App\Enums\DiscountAmountType;
use App\Enums\DiscountType;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
});

test('manager should be authenticated to create a discount', function () {
    $this->postJson(route('expert.discount.store', $this->hall->id))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->postJson(route('expert.discount.store', $this->hall->id))->assertUnauthorized();
});

test('type, amount type and amount are required', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.discount.store', $this->hall->id))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['type', 'amount_type', 'amount']);
});

test('a manual discount requires a user', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.discount.store', $this->hall->id), [
        'type' => 'manual',
        'amount_type' => 'fixed',
        'amount' => 5000,
    ])->assertStatus(422)->assertJsonValidationErrorFor('user_id');
});

test('a holiday discount requires a date window', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.discount.store', $this->hall->id), [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 10,
    ])->assertStatus(422)->assertJsonValidationErrors(['starts_at', 'ends_at']);
});

test('a percentage amount cannot exceed 100', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $this->postJson(route('expert.discount.store', $this->hall->id), [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 101,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDay()->toDateString(),
    ])->assertStatus(422)->assertJsonValidationErrorFor('amount');
});

test('manager can create a manual discount for a user', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $user = User::factory()->create();

    $response = $this->postJson(route('expert.discount.store', $this->hall->id), [
        'type' => 'manual',
        'user_id' => $user->id,
        'title' => 'vip',
        'amount_type' => 'fixed',
        'amount' => 5000,
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('discounts', [
        'id' => $response->json('data.discount_id'),
        'hall_id' => $this->hall->id,
        'type' => DiscountType::Manual->value,
        'user_id' => $user->id,
        'amount_type' => DiscountAmountType::Fixed->value,
        'amount' => 5000,
        'is_active' => 1,
        'created_by' => $this->owner->id,
    ]);
});

test('a holiday discount ignores a posted user id', function () {
    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $user = User::factory()->create();

    $response = $this->postJson(route('expert.discount.store', $this->hall->id), [
        'type' => 'holiday',
        'user_id' => $user->id,
        'amount_type' => 'percentage',
        'amount' => 15,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDays(3)->toDateString(),
    ]);

    $response->assertOk();
    $this->assertDatabaseHas('discounts', [
        'id' => $response->json('data.discount_id'),
        'type' => DiscountType::Holiday->value,
        'user_id' => null,
    ]);
});

test('manager cannot create a discount on a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->postJson(route('expert.discount.store', $this->hall->id), [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 10,
        'starts_at' => now()->toDateString(),
        'ends_at' => now()->addDay()->toDateString(),
    ])->assertForbidden();

    $this->assertDatabaseCount('discounts', 0);
});
