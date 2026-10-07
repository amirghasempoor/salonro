<?php

use App\Models\Discount;
use App\Models\Hall;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
});

test('user should be authenticated to see available discounts', function () {
    $this->getJson(route('user.discount.available', ['hall_id' => $this->hall->id]))
        ->assertUnauthorized();
});

test('hall_id is required', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.discount.available'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hall_id']);
});

test('it returns the user own manual discount and the hall active holiday discount', function () {
    $own = Discount::factory()->manual($this->user)->create(['hall_id' => $this->hall->id]);
    $holiday = Discount::factory()->holiday()->create(['hall_id' => $this->hall->id]);

    $otherHall = Hall::factory()->create();
    Discount::factory()->holiday()->create(['hall_id' => $otherHall->id]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.discount.available', ['hall_id' => $this->hall->id]));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    expect(collect($response->json('data'))->pluck('id')->sort()->values()->all())
        ->toBe(collect([$own->id, $holiday->id])->sort()->values()->all());
});
