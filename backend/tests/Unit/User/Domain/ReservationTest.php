<?php

use Tests\TestCase;
use User\Domain\Entities\Hall;
use User\Domain\Entities\Reservation;
use User\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use User\Domain\Exceptions\Reservation\ReservationConflictException;
use User\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;

uses(TestCase::class);

// 2026-09-21 is a Monday.
const MONDAY_9_TO_17 = [['day' => 'mon', 'from' => '09:00', 'to' => '17:00']];

function hallOffering(int $hallId = 7, string $hallName = 'Rose Salon'): Hall
{
    return new Hall($hallId, $hallName, [
        ['id' => 1, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 10, 'sub_cat_name' => 'Haircut', 'icon' => null, 'price' => 100, 'duration' => 30],
        ['id' => 2, 'cat_id' => 1, 'cat_name' => 'Hair', 'sub_cat_id' => 11, 'sub_cat_name' => 'Color', 'icon' => null, 'price' => 250, 'duration' => 90],
    ], []);
}

function busyReservation(int $id, string $start, string $finish): array
{
    return ['id' => $id, 'start' => new DateTimeImmutable($start), 'finish' => new DateTimeImmutable($finish)];
}

function bookReservationAt(string $start, string $finish, array $serviceIds = [1], array $hours = MONDAY_9_TO_17, array $busySlots = []): Reservation
{
    return Reservation::book(
        hall: hallOffering(),
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

function existingUserReservation(): Reservation
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

test('booking snapshots the people and hall and prices the services from the hall', function () {
    $reservation = bookReservationAt('2026-09-21 10:00', '2026-09-21 11:00', [1, 2]);

    expect($reservation->id)->toBeNull()
        ->and($reservation->hallId)->toBe(7)
        ->and($reservation->hallName)->toBe('Rose Salon')
        ->and($reservation->baseTotal)->toBe(350)
        ->and(array_keys($reservation->lines))->toBe([1, 2]);
});

test('booking that starts before the working window is rejected', function () {
    bookReservationAt('2026-09-21 08:59', '2026-09-21 10:00');
})->throws(OutsideWorkingHoursException::class);

test('booking with no working hours at all is rejected', function () {
    bookReservationAt('2026-09-21 10:00', '2026-09-21 11:00', hours: []);
})->throws(OutsideWorkingHoursException::class);

test('booking over an existing reservation is a conflict', function () {
    bookReservationAt('2026-09-21 10:30', '2026-09-21 11:30', busySlots: [busyReservation(1, '2026-09-21 10:00', '2026-09-21 11:00')]);
})->throws(ReservationConflictException::class);

test('back-to-back reservations do not conflict', function () {
    $busySlots = [busyReservation(1, '2026-09-21 10:00', '2026-09-21 11:00')];

    expect(bookReservationAt('2026-09-21 11:00', '2026-09-21 12:00', busySlots: $busySlots))->toBeInstanceOf(Reservation::class);
});

test('booking a service the hall does not offer is rejected', function () {
    bookReservationAt('2026-09-21 10:00', '2026-09-21 11:00', [99]);
})->throws(ServiceNotOfferedByHallException::class);

test('rescheduling can move the reservation to a different hall and expert', function () {
    $otherHall = hallOffering(9, 'Lotus Salon');

    $rescheduled = existingUserReservation()->reschedule(
        hall: $otherHall,
        expertId: 33,
        expertName: 'Different Expert',
        serviceIds: [2],
        start: new DateTimeImmutable('2026-09-21 13:00'),
        finish: new DateTimeImmutable('2026-09-21 15:00'),
        workingHours: MONDAY_9_TO_17,
        busySlots: [],
    );

    expect($rescheduled->id)->toBe(5)
        ->and($rescheduled->userId)->toBe(11)
        ->and($rescheduled->hallId)->toBe(9)
        ->and($rescheduled->hallName)->toBe('Lotus Salon')
        ->and($rescheduled->expertId)->toBe(33)
        ->and($rescheduled->expertName)->toBe('Different Expert')
        ->and($rescheduled->baseTotal)->toBe(250);
});

test('rescheduling leaves the original reservation untouched', function () {
    $original = existingUserReservation();

    $original->reschedule(
        hall: hallOffering(),
        expertId: 22,
        expertName: 'Neda Karimi',
        serviceIds: [2],
        start: new DateTimeImmutable('2026-09-21 13:00'),
        finish: new DateTimeImmutable('2026-09-21 15:00'),
        workingHours: MONDAY_9_TO_17,
        busySlots: [],
    );

    expect($original->hallId)->toBe(7)
        ->and($original->baseTotal)->toBe(100);
});

test('a reservation does not conflict with its own current slot', function () {
    $busySlots = [busyReservation(5, '2026-09-21 10:00', '2026-09-21 11:00')];

    $moved = existingUserReservation()->reschedule(
        hall: hallOffering(),
        expertId: 22,
        expertName: 'Neda Karimi',
        serviceIds: [1],
        start: new DateTimeImmutable('2026-09-21 10:30'),
        finish: new DateTimeImmutable('2026-09-21 11:30'),
        workingHours: MONDAY_9_TO_17,
        busySlots: $busySlots,
    );

    expect($moved->start->format('H:i'))->toBe('10:30');
});
