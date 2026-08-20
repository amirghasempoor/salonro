<?php

use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\User;

beforeEach(function () {
    $this->expert = Expert::factory()->create();
    $this->actingAs($this->expert, 'expert');

    $this->hall = Hall::factory()->create(['owner_id' => $this->expert->id]);
});

test('manager can create a manual discount for a customer', function () {
    $customer = User::factory()->create();

    $this->postJson("/expert/discounts/{$this->hall->id}", [
        'type' => 'manual',
        'user_id' => $customer->id,
        'amount_type' => 'fixed',
        'amount' => 50000,
        'title' => 'VIP',
    ])->assertOk();

    $this->assertDatabaseHas('discounts', [
        'hall_id' => $this->hall->id,
        'type' => 'manual',
        'user_id' => $customer->id,
        'amount_type' => 'fixed',
        'amount' => 50000,
        'created_by' => $this->expert->id,
    ]);
});

test('manager can create a holiday discount', function () {
    $this->postJson("/expert/discounts/{$this->hall->id}", [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 15,
        'starts_at' => '2026-03-20',
        'ends_at' => '2026-03-25',
        'title' => 'Nowruz',
    ])->assertOk();

    $this->assertDatabaseHas('discounts', [
        'hall_id' => $this->hall->id,
        'type' => 'holiday',
        'amount' => 15,
        'user_id' => null,
    ]);
});

test('manager cannot create a discount for a hall they do not own', function () {
    $otherHall = Hall::factory()->create();

    $this->postJson("/expert/discounts/{$otherHall->id}", [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 10,
        'starts_at' => '2026-03-20',
        'ends_at' => '2026-03-25',
    ])->assertStatus(403);
});

test('manager can list a hall\'s discounts', function () {
    Discount::factory()->holiday()->count(2)->create(['hall_id' => $this->hall->id]);

    $this->getJson("/expert/discounts/{$this->hall->id}?filters=[]&sorting=[]")
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('manager can view a discount', function () {
    $discount = Discount::factory()->holiday()->create(['hall_id' => $this->hall->id]);

    $this->getJson("/expert/discounts/details/{$this->hall->id}/{$discount->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $discount->id);
});

test('viewing a discount that belongs to another hall returns 404', function () {
    $otherHall = Hall::factory()->create(['owner_id' => $this->expert->id]);
    $discount = Discount::factory()->holiday()->create(['hall_id' => $otherHall->id]);

    $this->getJson("/expert/discounts/details/{$this->hall->id}/{$discount->id}")
        ->assertStatus(404);
});

test('manager can update a discount', function () {
    $discount = Discount::factory()->holiday()->percentage(10)->create(['hall_id' => $this->hall->id]);

    $this->postJson("/expert/discounts/{$this->hall->id}/{$discount->id}", [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 25,
        'starts_at' => '2026-03-20',
        'ends_at' => '2026-03-25',
        'is_active' => true,
    ])->assertOk();

    $this->assertDatabaseHas('discounts', ['id' => $discount->id, 'amount' => 25]);
});

test('manager can delete a discount', function () {
    $discount = Discount::factory()->holiday()->create(['hall_id' => $this->hall->id]);

    $this->deleteJson("/expert/discounts/{$this->hall->id}/{$discount->id}")->assertOk();

    $this->assertDatabaseMissing('discounts', ['id' => $discount->id]);
});

test('a manual discount requires a user_id', function () {
    $this->postJson("/expert/discounts/{$this->hall->id}", [
        'type' => 'manual',
        'amount_type' => 'fixed',
        'amount' => 50000,
    ])->assertStatus(422);
});

test('a holiday discount requires a date window', function () {
    $this->postJson("/expert/discounts/{$this->hall->id}", [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 10,
    ])->assertStatus(422);
});

test('a percentage discount cannot exceed 100', function () {
    $this->postJson("/expert/discounts/{$this->hall->id}", [
        'type' => 'holiday',
        'amount_type' => 'percentage',
        'amount' => 150,
        'starts_at' => '2026-03-20',
        'ends_at' => '2026-03-25',
    ])->assertStatus(422);
});
