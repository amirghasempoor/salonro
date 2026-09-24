<?php

use App\Enums\ReservationStates;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\HallService;
use App\Models\Reservation;
use App\Models\Service;
use Expert\Domain\Actions\Reservation\RescheduleReservationAction;
use Expert\Domain\DTOs\Reservation\RescheduleReservationDto;
use Expert\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use Expert\Domain\Exceptions\Reservation\ReservationConflictException;
use Expert\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;
use Illuminate\Support\Carbon;

beforeEach(function () {
    seedReservationStates();

    $this->hall = Hall::factory()->create();
    $this->expert = Expert::query()->find($this->hall->owner_id);
    $this->service = Service::factory()->create();

    HallService::factory()->create([
        'hall_id' => $this->hall->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 90,
    ]);

    $this->expertHall = ExpertHall::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
    ]);
    seedFullWeekWorkingHours($this->expertHall);

    $this->start = Carbon::now()->addDays(2)->setTime(10, 0)->toImmutable();
    $this->finish = $this->start->addHour();

    $this->reservation = Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'hall_id' => $this->hall->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->start,
        'finish_time' => $this->finish,
        'total_price' => 5,
    ]);
});

function rescheduleDto(object $test, array $overrides = []): RescheduleReservationDto
{
    return new RescheduleReservationDto(...array_merge([
        'serviceIds' => [$test->service->id],
        'start' => $test->start->addHours(3),
        'finish' => $test->finish->addHours(3),
    ], $overrides));
}

test('it moves the reservation and re-prices it from the hall services', function () {
    $rescheduled = app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this));

    expect($rescheduled->id)->toBe($this->reservation->id)
        ->and($rescheduled->baseTotal)->toBe(200000);

    $this->reservation->refresh();
    expect($this->reservation->start_time->format('H:i'))->toBe('13:00')
        ->and($this->reservation->finish_time->format('H:i'))->toBe('14:00')
        ->and((int) $this->reservation->total_price)->toBe(200000);

    $this->assertDatabaseHas('reservation_services', [
        'reservation_id' => $this->reservation->id,
        'service_id' => $this->service->id,
        'price' => 200000,
        'duration' => 90,
    ]);
});

test('it replaces the previous services', function () {
    $old = Service::factory()->create();
    $this->reservation->services()->attach($old->id, ['service_name' => 'old', 'price' => 1, 'duration' => 1]);

    app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this));

    $this->assertDatabaseMissing('reservation_services', ['reservation_id' => $this->reservation->id, 'service_id' => $old->id]);
    $this->assertDatabaseHas('reservation_services', ['reservation_id' => $this->reservation->id, 'service_id' => $this->service->id]);
});

test('it recomputes an already granted discount against the new total', function () {
    $discount = Discount::factory()->holiday()->percentage(10)->create(['hall_id' => $this->hall->id]);
    $this->reservation->update(['discount_id' => $discount->id, 'discount_amount' => 1, 'total_price' => 1]);

    app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this));

    $this->assertDatabaseHas('reservations', [
        'id' => $this->reservation->id,
        'discount_id' => $discount->id,
        'discount_amount' => 20000,
        'total_price' => 180000,
    ]);
});

test('it does not conflict with the reservation own current slot', function () {
    $rescheduled = app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this, [
        'start' => $this->start->addMinutes(15),
        'finish' => $this->finish->addMinutes(15),
    ]));

    expect($rescheduled->start->format('H:i'))->toBe('10:15');
});

test('it rejects a slot overlapping the expert other reservation', function () {
    Reservation::factory()->create([
        'expert_id' => $this->expert->id,
        'state_id' => ReservationStates::Reserve->value,
        'start_time' => $this->start->addHours(3),
        'finish_time' => $this->finish->addHours(3),
    ]);

    app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this));
})->throws(ReservationConflictException::class);

test('it rejects a slot outside the expert working hours and leaves the reservation untouched', function () {
    $this->expertHall->workingHours()->delete();

    expect(fn () => app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this)))
        ->toThrow(OutsideWorkingHoursException::class);

    expect($this->reservation->fresh()->start_time->format('H:i'))->toBe('10:00');
});

test('it rejects a service the hall does not offer', function () {
    $other = Service::factory()->create();

    app(RescheduleReservationAction::class)->execute($this->reservation->id, rescheduleDto($this, ['serviceIds' => [$other->id]]));
})->throws(ServiceNotOfferedByHallException::class);
