<?php

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\User;
use App\Models\WorkingHour;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);

    // 2026-09-21 is a Monday.
    $this->from = now()->setDate(2026, 9, 21)->startOfDay();
    $this->to = $this->from->copy()->addDay();
});

function staffScheduleQuery(object $test, array $overrides = []): array
{
    return array_merge([
        'expert_id' => $test->expert->id,
        'hall_id' => $test->hall->id,
        'from_date' => $test->from->toDateTimeString(),
        'to_date' => $test->to->toDateTimeString(),
    ], $overrides);
}

test('user should be authenticated to see a staff schedule', function () {
    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)))
        ->assertUnauthorized();
});

test('user should be authenticated with guard user', function () {
    Sanctum::actingAs($this->user, ['*'], 'expert');

    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)))
        ->assertUnauthorized();
});

test('expert_id, hall_id, from_date and to_date are required', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffSchedule'))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['expert_id', 'hall_id', 'from_date', 'to_date']);
});

test('to_date must not be before from_date', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this, [
        'to_date' => $this->from->copy()->subDay()->toDateTimeString(),
    ])))
        ->assertStatus(422)
        ->assertJsonValidationErrorFor('to_date');
});

test('with no working hours defined there are no free slots', function () {
    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('a day with no bookings is entirely free, bounded by the working hours', function () {
    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'mon',
        'from' => '09:00:00',
        'to' => '17:00:00',
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)));

    $response->assertOk();
    $response->assertJsonCount(1, 'data');
    $response->assertJsonPath('data.0.start_time', '2026-09-21 09:00:00');
    $response->assertJsonPath('data.0.finish_time', '2026-09-21 17:00:00');
});

test('a booking in the middle of the working hours splits it into two free slots', function () {
    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'mon',
        'from' => '09:00:00',
        'to' => '17:00:00',
    ]);
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->setTime(12, 0),
        'finish_time' => $this->from->copy()->setTime(13, 0),
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.finish_time', '2026-09-21 12:00:00');
    $response->assertJsonPath('data.1.start_time', '2026-09-21 13:00:00');
});

test('a cancelled booking does not remove its slot from availability', function () {
    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'mon',
        'from' => '09:00:00',
        'to' => '17:00:00',
    ]);
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Cancel->value,
        'start_time' => $this->from->copy()->setTime(12, 0),
        'finish_time' => $this->from->copy()->setTime(13, 0),
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.start_time', '2026-09-21 09:00:00')
        ->assertJsonPath('data.0.finish_time', '2026-09-21 17:00:00');
});

test('a booking at another hall still blocks the expert everywhere', function () {
    $otherHall = Hall::factory()->create();

    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'mon',
        'from' => '09:00:00',
        'to' => '17:00:00',
    ]);
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $otherHall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->setTime(12, 0),
        'finish_time' => $this->from->copy()->setTime(13, 0),
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('another expert booking does not affect this expert availability', function () {
    $otherExpert = Expert::factory()->create();

    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'mon',
        'from' => '09:00:00',
        'to' => '17:00:00',
    ]);
    Reservation::factory()->create([
        'expert_id' => $otherExpert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->from->copy()->setTime(12, 0),
        'finish_time' => $this->from->copy()->setTime(13, 0),
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this)))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.start_time', '2026-09-21 09:00:00')
        ->assertJsonPath('data.0.finish_time', '2026-09-21 17:00:00');
});

test('it spans several days, expanding the recurring schedule for each matching day', function () {
    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'mon',
        'from' => '09:00:00',
        'to' => '17:00:00',
    ]);
    WorkingHour::factory()->create([
        'hourable_id' => $this->expertHall->id,
        'hourable_type' => ExpertHall::class,
        'day' => 'tue',
        'from' => '10:00:00',
        'to' => '14:00:00',
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');

    $response = $this->getJson(route('user.reservation.staffSchedule', staffScheduleQuery($this, [
        'to_date' => $this->to->copy()->addDay()->toDateTimeString(),
    ])));

    $response->assertOk();
    $response->assertJsonCount(2, 'data');
    $response->assertJsonPath('data.0.start_time', '2026-09-21 09:00:00');
    $response->assertJsonPath('data.1.start_time', '2026-09-22 10:00:00');
});
