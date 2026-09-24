<?php

namespace Expert\Domain\Entities;

use DateTimeImmutable;
use DateTimeInterface;
use Expert\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use Expert\Domain\Exceptions\Reservation\ReservationConflictException;
use Expert\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;

/**
 * A booking of an expert for a customer at a hall.
 *
 * It owns the booking rules: the slot must fit the expert's working hours and
 * must not overlap any active reservation of the expert or the customer, and
 * services are priced from the hall — never from client input.
 *
 * Immutable: rescheduling returns a new Reservation. $baseTotal is the price of
 * the services before any discount; discounts are applied afterwards.
 */
final readonly class Reservation
{
    /**
     * @param  array<int, array{service_name: string, price: int, duration: int|null}>  $lines  priced services keyed by service id
     */
    public function __construct(
        public ?int $id,
        public int $userId,
        public string $userName,
        public int $expertId,
        public string $expertName,
        public int $hallId,
        public string $hallName,
        public DateTimeImmutable $start,
        public DateTimeImmutable $finish,
        public array $lines,
        public int $baseTotal,
    ) {}

    /**
     * @param  array<int, int>  $serviceIds
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours  the expert's windows at the hall
     * @param  array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>  $busySlots  active reservations of the expert or the customer
     *
     * @throws OutsideWorkingHoursException
     * @throws ReservationConflictException
     * @throws ServiceNotOfferedByHallException
     */
    public static function book(
        Hall $hall,
        int $userId,
        string $userName,
        int $expertId,
        string $expertName,
        array $serviceIds,
        DateTimeInterface $start,
        DateTimeInterface $finish,
        array $workingHours,
        array $busySlots,
    ): self {
        self::assertAvailable($start, $finish, $workingHours, $busySlots, ignoreId: null);

        $priced = $hall->priceServices($serviceIds);

        return new self(
            id: null,
            userId: $userId,
            userName: $userName,
            expertId: $expertId,
            expertName: $expertName,
            hallId: $hall->id,
            hallName: $hall->name,
            start: DateTimeImmutable::createFromInterface($start),
            finish: DateTimeImmutable::createFromInterface($finish),
            lines: $priced['lines'],
            baseTotal: $priced['baseTotal'],
        );
    }

    /**
     * The same reservation once persistence has assigned it an id.
     */
    public function withId(int $id): self
    {
        return new self(
            id: $id,
            userId: $this->userId,
            userName: $this->userName,
            expertId: $this->expertId,
            expertName: $this->expertName,
            hallId: $this->hallId,
            hallName: $this->hallName,
            start: $this->start,
            finish: $this->finish,
            lines: $this->lines,
            baseTotal: $this->baseTotal,
        );
    }

    /**
     * Move the reservation to a new slot and re-price its services. The
     * reservation's own current slot is never a conflict with itself.
     *
     * @param  array<int, int>  $serviceIds
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     * @param  array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>  $busySlots
     *
     * @throws OutsideWorkingHoursException
     * @throws ReservationConflictException
     * @throws ServiceNotOfferedByHallException
     */
    public function reschedule(
        Hall $hall,
        array $serviceIds,
        DateTimeInterface $start,
        DateTimeInterface $finish,
        array $workingHours,
        array $busySlots,
    ): self {
        self::assertAvailable($start, $finish, $workingHours, $busySlots, ignoreId: $this->id);

        $priced = $hall->priceServices($serviceIds);

        return new self(
            id: $this->id,
            userId: $this->userId,
            userName: $this->userName,
            expertId: $this->expertId,
            expertName: $this->expertName,
            hallId: $this->hallId,
            hallName: $this->hallName,
            start: DateTimeImmutable::createFromInterface($start),
            finish: DateTimeImmutable::createFromInterface($finish),
            lines: $priced['lines'],
            baseTotal: $priced['baseTotal'],
        );
    }

    /**
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     * @param  array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>  $busySlots
     */
    private static function assertAvailable(
        DateTimeInterface $start,
        DateTimeInterface $finish,
        array $workingHours,
        array $busySlots,
        ?int $ignoreId,
    ): void {
        throw_unless(self::fitsWorkingHours($start, $finish, $workingHours), OutsideWorkingHoursException::forSlot());
        throw_if(self::conflicts($start, $finish, $busySlots, $ignoreId), ReservationConflictException::forSlot());
    }

    /**
     * The slot must stay within a single day and inside one of that day's windows.
     *
     * @param  array<int, array{day: string, from: string, to: string}>  $workingHours
     */
    private static function fitsWorkingHours(DateTimeInterface $start, DateTimeInterface $finish, array $workingHours): bool
    {
        if ($start->format('Y-m-d') !== $finish->format('Y-m-d')) {
            return false;
        }

        $day = strtolower($start->format('D'));
        $startSeconds = self::secondsOfDay($start->format('H:i:s'));
        $finishSeconds = self::secondsOfDay($finish->format('H:i:s'));

        foreach ($workingHours as $window) {
            if (strtolower($window['day']) === $day
                && self::secondsOfDay($window['from']) <= $startSeconds
                && self::secondsOfDay($window['to']) >= $finishSeconds) {
                return true;
            }
        }

        return false;
    }

    /**
     * Back-to-back reservations (one finishing as the next starts) do not conflict.
     *
     * @param  array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>  $busySlots
     */
    private static function conflicts(DateTimeInterface $start, DateTimeInterface $finish, array $busySlots, ?int $ignoreId): bool
    {
        foreach ($busySlots as $busy) {
            if ($busy['id'] === $ignoreId) {
                continue;
            }

            if ($busy['start'] < $finish && $busy['finish'] > $start) {
                return true;
            }
        }

        return false;
    }

    /**
     * Working hours are stored loosely ('8:00' and '08:00:00' both occur), so
     * they are compared as numbers, never as text.
     */
    private static function secondsOfDay(string $time): int
    {
        [$hours, $minutes, $seconds] = array_map('intval', explode(':', $time) + [0, 0, 0]);

        return $hours * 3600 + $minutes * 60 + $seconds;
    }
}
