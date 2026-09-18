<?php

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->reservation = Reservation::factory()->create(['hall_id' => $this->hall->id]);
});

test('expert should be authenticated to see a reservation', function () {
    $this->getJson(route('expert.reservation.show', [$this->reservation->id]))->assertUnauthorized();
});

test('expert should be authenticated with guard expert', function () {
    $expert = Expert::factory()->create();
    Sanctum::actingAs($expert, ['*'], 'user');
    $this->getJson(route('expert.reservation.show', [$this->reservation->id]))->assertUnauthorized();
});

test('expert without access to the reservation hall is forbidden', function () {
    $stranger = Expert::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'expert');
    $this->getJson(route('expert.reservation.show', [$this->reservation->id]))->assertForbidden();
});

test('hall owner can see the reservation details', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');

    $response = $this->getJson(route('expert.reservation.show', [$this->reservation->id]));

    $response->assertOk();
    $response->assertJsonStructure([
        'data' => [
            'expert_name',
            'user_name',
            'discount_amount',
            'total_price',
            'start_time',
            'finish_time',
            'hall_name',
        ],
    ]);
    $response->assertJsonPath('data.hall_name', $this->reservation->hall_name);
});
