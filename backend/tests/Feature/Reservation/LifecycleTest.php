<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// Only a reserved, upcoming booking can be cancelled or moved; finished history
// and cancelled records stay exactly as they were.
beforeEach(function () {
    seedReservationStates();

    $this->hall = Hall::factory()->create();
    $this->owner = Expert::query()->find($this->hall->owner_id);
    $this->user = User::factory()->create();
    $this->service = Service::factory()->create();
    HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);

    $this->membership = ExpertHall::factory()->create(['expert_id' => $this->owner->id, 'hall_id' => $this->hall->id]);
    seedFullWeekWorkingHours($this->membership);
    ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $this->membership->id,
        'service_id' => $this->service->id,
        'price' => 100000,
        'duration' => 60,
    ]);
});

function reservationIn($test, int $state, $start): Reservation
{
    return Reservation::factory()->create([
        'user_id' => $test->user->id,
        'expert_id' => $test->owner->id,
        'hall_id' => $test->hall->id,
        'state_id' => $state,
        'start_time' => $start,
        'finish_time' => $start->copy()->addHour(),
    ]);
}

function userMove($test, Reservation $reservation, $start)
{
    return $test->postJson(route('user.reservation.update', $reservation->id), [
        'expert_id' => $test->owner->id,
        'hall_id' => $test->hall->id,
        'services' => [$test->service->id],
        'start_time' => $start->toDateTimeString(),
        'finish_time' => $start->copy()->addHour()->toDateTimeString(),
    ]);
}

test('a finished reservation cannot be cancelled by the customer or the hall', function () {
    $done = reservationIn($this, ReservationStates::Done->value, now()->subDay());

    Sanctum::actingAs($this->user, ['*'], 'user');
    $this->deleteJson(route('user.reservation.destroy', $done->id))
        ->assertStatus(422)->assertJsonPath('message', __('messages.reservation_cannot_be_cancelled'));

    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $this->deleteJson(route('expert.reservation.destroy', [$this->hall->id, $done->id]))->assertStatus(422);

    expect($done->fresh()->state_id)->toBe(ReservationStates::Done->value);
});

test('a reserved booking that already ended cannot be cancelled', function () {
    $past = reservationIn($this, ReservationStates::Reserve->value, now()->subDays(3));

    Sanctum::actingAs($this->user, ['*'], 'user');
    $this->deleteJson(route('user.reservation.destroy', $past->id))->assertStatus(422);

    expect($past->fresh()->state_id)->toBe(ReservationStates::Reserve->value);
});

test('a cancelled or past reservation cannot be rescheduled by the customer', function () {
    $cancelled = reservationIn($this, ReservationStates::Cancel->value, now()->addDays(2));
    $past = reservationIn($this, ReservationStates::Reserve->value, now()->subDays(5));
    $target = now()->addDays(3)->setTime(10, 0);

    Sanctum::actingAs($this->user, ['*'], 'user');
    userMove($this, $cancelled, $target)
        ->assertStatus(422)->assertJsonPath('message', __('messages.reservation_cannot_be_rescheduled'));
    userMove($this, $past, $target)->assertStatus(422);

    expect($cancelled->fresh()->start_time->toDateString())->toBe(now()->addDays(2)->toDateString());
});

test('the hall cannot move a cancelled reservation or move one into the past', function () {
    $cancelled = reservationIn($this, ReservationStates::Cancel->value, now()->addDays(2));
    $upcoming = reservationIn($this, ReservationStates::Reserve->value, now()->addDays(4)->setTime(9, 0));

    Sanctum::actingAs($this->owner, ['*'], 'expert');
    $body = fn ($start) => [
        'services' => [['service_id' => $this->service->id]],
        'start_time' => $start->toDateTimeString(),
        'finish_time' => $start->copy()->addHour()->toDateTimeString(),
    ];

    $this->postJson(route('expert.reservation.update', [$this->hall->id, $cancelled->id]), $body(now()->addDays(3)->setTime(10, 0)))
        ->assertStatus(422);
    $this->postJson(route('expert.reservation.update', [$this->hall->id, $upcoming->id]), $body(now()->subDays(2)->setTime(10, 0)))
        ->assertStatus(422)->assertJsonValidationErrors('start_time');
});

test('an upcoming reserved booking can still be moved and cancelled', function () {
    $booking = reservationIn($this, ReservationStates::Reserve->value, now()->addDays(2)->setTime(9, 0));

    Sanctum::actingAs($this->user, ['*'], 'user');
    userMove($this, $booking, now()->addDays(3)->setTime(10, 0))->assertOk();
    $this->deleteJson(route('user.reservation.destroy', $booking->id))->assertOk();

    expect($booking->fresh()->state_id)->toBe(ReservationStates::Cancel->value);
});

test('moving a booking outside the discount window drops the discount', function () {
    $discount = Discount::factory()->holiday()->percentage(50)->create([
        'hall_id' => $this->hall->id,
        'starts_at' => now()->addDay()->toDateString(),
        'ends_at' => now()->addDays(2)->toDateString(),
    ]);
    $booking = reservationIn($this, ReservationStates::Reserve->value, now()->addDay()->setTime(9, 0));
    $booking->update(['discount_id' => $discount->id, 'discount_amount' => 50000, 'total_price' => 50000]);

    Sanctum::actingAs($this->user, ['*'], 'user');
    userMove($this, $booking, now()->addDays(10)->setTime(10, 0))->assertOk();

    $booking->refresh();
    expect($booking->discount_id)->toBeNull()
        ->and((int) $booking->discount_amount)->toBe(0)
        ->and((int) $booking->total_price)->toBe(100000);
});
