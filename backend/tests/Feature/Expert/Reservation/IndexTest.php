<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
});

test('expert should be authenticated to list hall reservations', function () {
    $this->getJson(route('expert.reservation.index', [$this->hall->id]))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.reservation.index', [$this->hall->id]))->assertUnauthorized();
});

test('expert without access to the hall is forbidden', function () {
    $stranger = Expert::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'expert');
    $this->getJson(route('expert.reservation.index', [$this->hall->id]))->assertForbidden();
});

test('hall owner can list only that hall reservations', function () {
    Reservation::factory()->count(2)->create(['hall_id' => $this->hall->id]);

    $otherHall = Hall::factory()->create();
    Reservation::factory()->create(['hall_id' => $otherHall->id]);

    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $response = $this->getJson(route('expert.reservation.index', [
        'hall' => $this->hall->id,
        'start' => 0,
        'size' => 10,
        'filters' => json_encode([]),
        'sorting' => json_encode([]),
    ]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            [
                'id', 'user_name', 'state_name', 'expert_name', 'start_time', 'finish_time',
                'total_price', 'discount_id', 'discount_amount',
            ],
        ],
        'meta' => ['totalRowCount'],
    ]);
});
