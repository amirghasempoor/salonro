<?php

use User\Domain\Entities\StaffAvailability;

// 2026-09-21 is a Monday, 2026-09-22 is a Tuesday.
function busySlot(string $start, string $finish): array
{
    return ['start' => new DateTimeImmutable($start), 'finish' => new DateTimeImmutable($finish)];
}

test('with no working hours there are no free slots', function () {
    $availability = new StaffAvailability([]);

    $slots = $availability->freeSlots(new DateTimeImmutable('2026-09-21 00:00'), new DateTimeImmutable('2026-09-22 00:00'), []);

    expect($slots)->toBe([]);
});

test('a day with no bookings is entirely free, clipped to the working window', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(new DateTimeImmutable('2026-09-21 00:00'), new DateTimeImmutable('2026-09-22 00:00'), []);

    expect($slots)->toHaveCount(1)
        ->and($slots[0]['start']->format('Y-m-d H:i'))->toBe('2026-09-21 09:00')
        ->and($slots[0]['finish']->format('Y-m-d H:i'))->toBe('2026-09-21 17:00');
});

test('a booking in the middle of the window splits it into two free slots', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(
        new DateTimeImmutable('2026-09-21 00:00'),
        new DateTimeImmutable('2026-09-22 00:00'),
        [busySlot('2026-09-21 12:00', '2026-09-21 13:00')],
    );

    expect($slots)->toHaveCount(2)
        ->and($slots[0]['start']->format('H:i'))->toBe('09:00')
        ->and($slots[0]['finish']->format('H:i'))->toBe('12:00')
        ->and($slots[1]['start']->format('H:i'))->toBe('13:00')
        ->and($slots[1]['finish']->format('H:i'))->toBe('17:00');
});

test('a booking covering the whole window leaves no free slots that day', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(
        new DateTimeImmutable('2026-09-21 00:00'),
        new DateTimeImmutable('2026-09-22 00:00'),
        [busySlot('2026-09-21 09:00', '2026-09-21 17:00')],
    );

    expect($slots)->toBe([]);
});

test('a booking starting before the window and ending inside it only eats the overlap', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(
        new DateTimeImmutable('2026-09-21 00:00'),
        new DateTimeImmutable('2026-09-22 00:00'),
        [busySlot('2026-09-21 07:00', '2026-09-21 10:00')],
    );

    expect($slots)->toHaveCount(1)
        ->and($slots[0]['start']->format('H:i'))->toBe('10:00')
        ->and($slots[0]['finish']->format('H:i'))->toBe('17:00');
});

test('a booking outside the working window does not affect it', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(
        new DateTimeImmutable('2026-09-21 00:00'),
        new DateTimeImmutable('2026-09-22 00:00'),
        [busySlot('2026-09-21 18:00', '2026-09-21 19:00')],
    );

    expect($slots)->toHaveCount(1)
        ->and($slots[0]['start']->format('H:i'))->toBe('09:00')
        ->and($slots[0]['finish']->format('H:i'))->toBe('17:00');
});

test('a day with no matching working hours contributes no free slots', function () {
    $availability = new StaffAvailability([
        ['day' => 'tue', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(new DateTimeImmutable('2026-09-21 00:00'), new DateTimeImmutable('2026-09-22 00:00'), []);

    expect($slots)->toBe([]);
});

test('the period spanning several days expands the recurring schedule for each matching day', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
        ['day' => 'tue', 'from' => '10:00', 'to' => '14:00'],
    ]);

    $slots = $availability->freeSlots(new DateTimeImmutable('2026-09-21 00:00'), new DateTimeImmutable('2026-09-23 00:00'), []);

    expect($slots)->toHaveCount(2)
        ->and($slots[0]['start']->format('Y-m-d H:i'))->toBe('2026-09-21 09:00')
        ->and($slots[0]['finish']->format('Y-m-d H:i'))->toBe('2026-09-21 17:00')
        ->and($slots[1]['start']->format('Y-m-d H:i'))->toBe('2026-09-22 10:00')
        ->and($slots[1]['finish']->format('Y-m-d H:i'))->toBe('2026-09-22 14:00');
});

test('the requested period clips the working window, not just the day', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(new DateTimeImmutable('2026-09-21 11:00'), new DateTimeImmutable('2026-09-21 13:00'), []);

    expect($slots)->toHaveCount(1)
        ->and($slots[0]['start']->format('H:i'))->toBe('11:00')
        ->and($slots[0]['finish']->format('H:i'))->toBe('13:00');
});

test('unpadded working hours are handled the same as padded ones', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '9:00', 'to' => '17:00:00'],
    ]);

    $slots = $availability->freeSlots(new DateTimeImmutable('2026-09-21 00:00'), new DateTimeImmutable('2026-09-22 00:00'), []);

    expect($slots)->toHaveCount(1)
        ->and($slots[0]['start']->format('H:i'))->toBe('09:00');
});

test('a booking that exactly matches the working window boundary leaves adjoining free slots untouched', function () {
    $availability = new StaffAvailability([
        ['day' => 'mon', 'from' => '09:00', 'to' => '17:00'],
    ]);

    $slots = $availability->freeSlots(
        new DateTimeImmutable('2026-09-21 00:00'),
        new DateTimeImmutable('2026-09-22 00:00'),
        [busySlot('2026-09-21 12:00', '2026-09-21 12:30'), busySlot('2026-09-21 12:30', '2026-09-21 13:00')],
    );

    // back-to-back bookings merge into a single gap
    expect($slots)->toHaveCount(2)
        ->and($slots[0]['finish']->format('H:i'))->toBe('12:00')
        ->and($slots[1]['start']->format('H:i'))->toBe('13:00');
});
