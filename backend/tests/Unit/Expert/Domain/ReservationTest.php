<?php

use Expert\Domain\Entities\Hall;
use Expert\Domain\Entities\Reservation;
use Expert\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use Expert\Domain\Exceptions\Reservation\ReservationConflictException;
use Expert\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;
use Tests\TestCase;

uses(TestCase::class);

// 2026-09-21 is a Monday.
const MONDAY_9_TO_17 = [['day' => 'mon', 'from' => '09:00', 'to' => '17:00']];

function reservationHall(): Hall
{
    return new Hall(7, 'Rose Salon', [
        1 => ['name' => 'Haircut', 'price' => 100, 'duration' => 30],
        2 => ['name' => 'Color', 'price' => 250, 'duration' => 90],
    ]);
}

function busy(int $id, string $start, string $finish): array
{
    return ['id' => $id, 'start' => new DateTimeImmutable($start), 'finish' => new DateTimeImmutable($finish)];
}

function bookAt(string $start, string $finish, array $serviceIds = [1], array $hours = MONDAY_9_TO_17, array $busySlots = []): Reservation
{
    return Reservation::book(
        hall: reservationHall(),
        userId: 11,
        userName: 'Sara Ahmadi',
        expertId: 22,
        expertName: 'Neda Karimi',
        serviceIds: $serviceIds,
        start: new DateTimeImmutable($start),
        finish: new DateTimeImmutable($finish),
        workingHours: $hours,
        busySlots: $busySlots,
    );
}

function existingReservation(): Reservation
{
    return new Reservation(
        id: 5,
        userId: 11,
        userName: 'Sara Ahmadi',
        expertId: 22,
        expertName: 'Neda Karimi',
        hallId: 7,
        hallName: 'Rose Salon',
        start: new DateTimeImmutable('2026-09-21 10:00'),
        finish: new DateTimeImmutable('2026-09-21 11:00'),
        lines: [1 => ['service_name' => 'Haircut', 'price' => 100, 'duration' => 30]],
        baseTotal: 100,
    );
}

function rescheduleTo(Reservation $reservation, string $start, string $finish, array $serviceIds = [1], array $hours = MONDAY_9_TO_17, array $busySlots = []): Reservation
{
    return $reservation->reschedule(
        hall: reservationHall(),
        serviceIds: $serviceIds,
        start: new DateTimeImmutable($start),
        finish: new DateTimeImmutable($finish),
        workingHours: $hours,
        busySlots: $busySlots,
    );
}

// --- book ---

test('booking snapshots the people and hall and prices the services from the hall', function () {
    $reservation = bookAt('2026-09-21 10:00', '2026-09-21 11:00', [1, 2]);

    expect($reservation->id)->toBeNull()
        ->and($reservation->userId)->toBe(11)
        ->and($reservation->userName)->toBe('Sara Ahmadi')
        ->and($reservation->expertId)->toBe(22)
        ->and($reservation->expertName)->toBe('Neda Karimi')
        ->and($reservation->hallId)->toBe(7)
        ->and($reservation->hallName)->toBe('Rose Salon')
        ->and($reservation->baseTotal)->toBe(350)
        ->and(array_keys($reservation->lines))->toBe([1, 2])
        ->and($reservation->start->format('Y-m-d H:i'))->toBe('2026-09-21 10:00')
        ->and($reservation->finish->format('Y-m-d H:i'))->toBe('2026-09-21 11:00');
});

test('booking exactly filling the working window is allowed', function () {
    expect(bookAt('2026-09-21 09:00', '2026-09-21 17:00'))->toBeInstanceOf(Reservation::class);
});

test('booking that starts before the window is rejected', function () {
    bookAt('2026-09-21 08:59', '2026-09-21 10:00');
})->throws(OutsideWorkingHoursException::class);

test('booking that ends after the window is rejected', function () {
    bookAt('2026-09-21 16:00', '2026-09-21 17:01');
})->throws(OutsideWorkingHoursException::class);

test('booking on a day without working hours is rejected', function () {
    bookAt('2026-09-22 10:00', '2026-09-22 11:00');
})->throws(OutsideWorkingHoursException::class);

test('booking spanning midnight is rejected', function () {
    bookAt('2026-09-21 23:00', '2026-09-22 01:00', hours: [['day' => 'mon', 'from' => '00:00', 'to' => '23:59']]);
})->throws(OutsideWorkingHoursException::class);

test('unpadded working hours are compared as times, not text', function () {
    // as text '8:00' > '09:00'; as time 8:00 is earlier
    $hours = [['day' => 'mon', 'from' => '8:00', 'to' => '17:00']];

    expect(bookAt('2026-09-21 08:30', '2026-09-21 09:30', hours: $hours))->toBeInstanceOf(Reservation::class);
});

test('the matching window is picked among several on the same day', function () {
    $hours = [
        ['day' => 'mon', 'from' => '09:00', 'to' => '12:00'],
        ['day' => 'mon', 'from' => '14:00', 'to' => '18:00'],
    ];

    expect(bookAt('2026-09-21 15:00', '2026-09-21 16:00', hours: $hours))->toBeInstanceOf(Reservation::class);
    expect(fn () => bookAt('2026-09-21 11:00', '2026-09-21 15:00', hours: $hours))
        ->toThrow(OutsideWorkingHoursException::class);
});

test('booking with no working hours at all is rejected', function () {
    bookAt('2026-09-21 10:00', '2026-09-21 11:00', hours: []);
})->throws(OutsideWorkingHoursException::class);

test('booking over an existing reservation is a conflict', function () {
    bookAt('2026-09-21 10:30', '2026-09-21 11:30', busySlots: [busy(1, '2026-09-21 10:00', '2026-09-21 11:00')]);
})->throws(ReservationConflictException::class);

test('booking that swallows an existing reservation is a conflict', function () {
    bookAt('2026-09-21 09:00', '2026-09-21 12:00', busySlots: [busy(1, '2026-09-21 10:00', '2026-09-21 11:00')]);
})->throws(ReservationConflictException::class);

test('back-to-back reservations do not conflict', function () {
    $busySlots = [busy(1, '2026-09-21 10:00', '2026-09-21 11:00')];

    expect(bookAt('2026-09-21 11:00', '2026-09-21 12:00', busySlots: $busySlots))->toBeInstanceOf(Reservation::class);
    expect(bookAt('2026-09-21 09:00', '2026-09-21 10:00', busySlots: $busySlots))->toBeInstanceOf(Reservation::class);
});

test('booking a service the hall does not offer is rejected', function () {
    bookAt('2026-09-21 10:00', '2026-09-21 11:00', [1, 99]);
})->throws(ServiceNotOfferedByHallException::class);

// --- reschedule ---

test('rescheduling moves the slot and re-prices the services, keeping identity', function () {
    $original = existingReservation();

    $moved = rescheduleTo($original, '2026-09-21 13:00', '2026-09-21 15:00', [2]);

    expect($moved->id)->toBe(5)
        ->and($moved->userId)->toBe(11)
        ->and($moved->expertId)->toBe(22)
        ->and($moved->hallId)->toBe(7)
        ->and($moved->start->format('H:i'))->toBe('13:00')
        ->and($moved->finish->format('H:i'))->toBe('15:00')
        ->and($moved->baseTotal)->toBe(250)
        ->and(array_keys($moved->lines))->toBe([2]);
});

test('rescheduling leaves the original reservation untouched', function () {
    $original = existingReservation();

    rescheduleTo($original, '2026-09-21 13:00', '2026-09-21 15:00', [2]);

    expect($original->start->format('H:i'))->toBe('10:00')
        ->and($original->baseTotal)->toBe(100);
});

test('a reservation does not conflict with its own current slot', function () {
    $busySlots = [busy(5, '2026-09-21 10:00', '2026-09-21 11:00')];

    $moved = rescheduleTo(existingReservation(), '2026-09-21 10:30', '2026-09-21 11:30', busySlots: $busySlots);

    expect($moved->start->format('H:i'))->toBe('10:30');
});

test('rescheduling onto another reservation is a conflict', function () {
    rescheduleTo(
        existingReservation(),
        '2026-09-21 13:30',
        '2026-09-21 14:30',
        busySlots: [busy(5, '2026-09-21 10:00', '2026-09-21 11:00'), busy(6, '2026-09-21 13:00', '2026-09-21 14:00')],
    );
})->throws(ReservationConflictException::class);

test('rescheduling outside the working hours is rejected', function () {
    rescheduleTo(existingReservation(), '2026-09-21 18:00', '2026-09-21 19:00');
})->throws(OutsideWorkingHoursException::class);

test('rescheduling to a service the hall does not offer is rejected', function () {
    rescheduleTo(existingReservation(), '2026-09-21 13:00', '2026-09-21 14:00', [99]);
})->throws(ServiceNotOfferedByHallException::class);
