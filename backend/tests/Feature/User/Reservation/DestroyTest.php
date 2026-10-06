<?php

use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->reservation = Reservation::factory()->create(['user_id' => $this->owner->id]);
});

test('user should be authenticated to delete a reservation', function () {
    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertUnauthorized();
});

test('the owning customer can delete their reservation', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');

    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertOk();

    $this->assertDatabaseMissing('reservations', ['id' => $this->reservation->id]);
});

test('another customer cannot delete someone else\'s reservation', function () {
    $stranger = User::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'user');

    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertForbidden();

    $this->assertDatabaseHas('reservations', ['id' => $this->reservation->id]);
});
