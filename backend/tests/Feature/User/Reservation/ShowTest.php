<?php

use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->reservation = Reservation::factory()->create(['user_id' => $this->owner->id]);
});

test('user should be authenticated to see a reservation', function () {
    $this->getJson(route('user.reservation.show', $this->reservation->id))->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $this->getJson(route('user.reservation.show', $this->reservation->id))->assertUnauthorized();
});

test('the owning customer can see their reservation', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');

    $this->getJson(route('user.reservation.show', $this->reservation->id))
        ->assertOk()
        ->assertJsonPath('data.id', $this->reservation->id);
});

test('another customer cannot see someone else\'s reservation', function () {
    $stranger = User::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'user');

    $this->getJson(route('user.reservation.show', $this->reservation->id))
        ->assertForbidden();
});
