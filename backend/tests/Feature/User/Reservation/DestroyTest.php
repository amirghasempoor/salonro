<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Reservation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();
    $this->owner = User::factory()->create();
    $this->reservation = Reservation::factory()->create([
        'user_id' => $this->owner->id,
        'state_id' => ReservationStates::Reserve->value,
    ]);
});

test('user should be authenticated to delete a reservation', function () {
    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertUnauthorized();
});

test('the owning customer can cancel their reservation, which is kept as a record', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');

    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertOk();

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'state_id' => ReservationStates::Cancel->value,
    ]);
});

test('cancelling twice is harmless', function () {
    Sanctum::actingAs($this->owner, ['*'], 'user');

    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertOk();
    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertOk();
});

test('cancelling gives back the use of a manual discount', function () {
    $discount = Discount::factory()->create(['type' => 'manual', 'usage_limit' => 1, 'used_count' => 1]);
    $this->reservation->forceFill(['discount_id' => $discount->id, 'discount_amount' => 10])->save();
    Sanctum::actingAs($this->owner, ['*'], 'user');

    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertOk();
    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertOk();

    expect($discount->fresh()->used_count)->toBe(0);
});

test('another customer cannot delete someone else\'s reservation', function () {
    $stranger = User::factory()->create();
    Sanctum::actingAs($stranger, ['*'], 'user');

    $this->deleteJson(route('user.reservation.destroy', $this->reservation->id))->assertForbidden();

    $this->assertDatabaseHas('reservations', ['id' => $this->reservation->id]);
});
