<?php

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Support\Carbon;

beforeEach(function () {
    seedReservationStates();

    $this->expert = Expert::factory()->create();
    $this->from = now()->addDay()->startOfDay();
    $this->to = $this->from->copy()->addDay();
});

function scheduleQuery(object $test, array $overrides = []): array
{
    return array_merge([
        'expert_id' => $test->expert->id,
        'from_date' => $test->from->toDateTimeString(),
        'to_date' => $test->to->toDateTimeString(),
    ], $overrides);
}

test('expert_id, from_date and to_date are required', function () {
    $this->getJson(route('user.reservation.staffSchedule'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['expert_id', 'from_date', 'to_date']);
});

test('to_date must not be before from_date', function () {
    $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this, [
        'to_date' => $this->from->copy()->subDay()->toDateTimeString(),
    ])))
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('to_date');
});

test('it returns the expert booked slots inside the requested period', function () {
    $reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->addHours(2),
        'finish_time' => $this->from->copy()->addHours(3),
    ]);

    $response = $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)));

    $response->assertOk();
    $data = $response->json('data');

    expect($data)->toHaveCount(1);
    expect(Carbon::parse($data[0]['start_time'])->eq($reservation->start_time))->toBeTrue();
    expect(Carbon::parse($data[0]['finish_time'])->eq($reservation->finish_time))->toBeTrue();
});

test('it ignores a cancelled reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Cancel->value,
        'start_time' => $this->from->copy()->addHours(2),
        'finish_time' => $this->from->copy()->addHours(3),
    ]);

    $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('it ignores a slot entirely outside the requested period', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->to->copy()->addHour(),
        'finish_time' => $this->to->copy()->addHours(2),
    ]);

    $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('it includes a slot that only partially overlaps the requested period', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->subMinutes(30),
        'finish_time' => $this->from->copy()->addMinutes(30),
    ]);

    $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('it ignores another expert reservations', function () {
    $otherExpert = Expert::factory()->create();
    Reservation::factory()->create([
        'expert_id' => $otherExpert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->addHours(2),
        'finish_time' => $this->from->copy()->addHours(3),
    ]);

    $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('it includes the expert slots regardless of which hall they were booked at', function () {
    $hallA = Hall::factory()->create();
    $hallB = Hall::factory()->create();

    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $hallA->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->addHours(2),
        'finish_time' => $this->from->copy()->addHours(3),
    ]);
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $hallB->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->addHours(5),
        'finish_time' => $this->from->copy()->addHours(6),
    ]);

    $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('it orders the slots by start time', function () {
    $later = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->addHours(5),
        'finish_time' => $this->from->copy()->addHours(6),
    ]);
    $earlier = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->addHours(1),
        'finish_time' => $this->from->copy()->addHours(2),
    ]);

    $response = $this->getJson(route('user.reservation.staffSchedule', scheduleQuery($this)));

    $response->assertOk();
    $data = $response->json('data');

    expect(Carbon::parse($data[0]['start_time'])->eq($earlier->start_time))->toBeTrue();
    expect(Carbon::parse($data[1]['start_time'])->eq($later->start_time))->toBeTrue();
});
