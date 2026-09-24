<?php

use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedRoles();
    $this->owner = Expert::factory()->create();
    $this->hall = Hall::factory()->create(['owner_id' => $this->owner->id]);
});

test('manager should be authenticated to list discounts', function () {
    $this->getJson(route('expert.discount.index', $this->hall->id))->assertUnauthorized();
});

test('manager should be authenticated with guard expert', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');
    $this->getJson(route('expert.discount.index', $this->hall->id))->assertUnauthorized();
});

test('manager sees only their hall discounts', function () {
    Discount::factory()->count(2)->create(['hall_id' => $this->hall->id]);
    Discount::factory()->count(3)->create();

    $this->owner->assignRole('manager');
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->getJson(route('expert.discount.index', [
        'hall' => $this->hall->id,
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    expect($response->json('data'))->toHaveCount(2);
});

test('manager cannot list discounts of a hall they do not own', function () {
    $intruder = Expert::factory()->create();
    $intruder->assignRole('manager');
    Sanctum::actingAs($intruder, ['*'], 'expert');

    $this->getJson(route('expert.discount.index', $this->hall->id))->assertForbidden();
});
