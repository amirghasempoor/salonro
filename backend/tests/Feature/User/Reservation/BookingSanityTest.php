<?php

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Service;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

// A booking has to make sense: in the future, and long enough for the services.
beforeEach(function () {
    seedReservationStates();

    $this->user = User::factory()->create();
    $this->hall = Hall::factory()->create();
    $this->expert = Expert::factory()->create();
    $this->service = Service::factory()->create();

    HallService::factory()->create(['hall_id' => $this->hall->id, 'service_id' => $this->service->id]);

    $expertHall = ExpertHall::factory()->create(['expert_id' => $this->expert->id, 'hall_id' => $this->hall->id]);
    seedFullWeekWorkingHours($expertHall);

    ExpertService::factory()->create([
        'serviceable_type' => ExpertHall::class,
        'serviceable_id' => $expertHall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 60,
    ]);

    Sanctum::actingAs($this->user, ['*'], 'user');
});

function bookingPayload($test, $start, $finish): array
{
    return [
        'expert_id' => $test->expert->id,
        'hall_id' => $test->hall->id,
        'services' => [$test->service->id],
        'start_time' => $start->toDateTimeString(),
        'finish_time' => $finish->toDateTimeString(),
    ];
}

test('a booking that starts in the past is rejected', function () {
    $start = now()->subDay()->setTime(10, 0);

    $this->postJson(route('user.reservation.store'), bookingPayload($this, $start, $start->copy()->addHour()))
        ->assertStatus(422)
        ->assertJsonValidationErrors('start_time');

    $this->assertDatabaseCount('reservations', 0);
});

test('a slot shorter than the service duration is rejected', function () {
    $start = now()->addDay()->setTime(10, 0);

    $this->postJson(route('user.reservation.store'), bookingPayload($this, $start, $start->copy()->addMinutes(10)))
        ->assertStatus(422)
        ->assertJsonPath('message', __('messages.slot_too_short'));

    $this->assertDatabaseCount('reservations', 0);
});

test('a slot exactly as long as the service, or longer, is accepted', function () {
    $start = now()->addDay()->setTime(10, 0);

    $this->postJson(route('user.reservation.store'), bookingPayload($this, $start, $start->copy()->addHour()))->assertOk();
    $this->postJson(route('user.reservation.store'), bookingPayload($this, $start->copy()->addHours(2), $start->copy()->addHours(4)))->assertOk();
});
