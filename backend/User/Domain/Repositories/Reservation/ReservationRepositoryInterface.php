<?php

namespace User\Domain\Repositories\Reservation;

use DateTimeInterface;
use User\Domain\Entities\Reservation;

interface ReservationRepositoryInterface
{
    public function expertName(int $expertId): string;

    /**
     * The expert's own working windows at the hall.
     *
     * @return array<int, array{day: string, from: string, to: string}>
     */
    public function workingHours(int $expertId, int $hallId): array;

    /**
     * Active (not cancelled) reservations of the expert or the customer on the
     * given day. The entity decides which of them actually conflict.
     *
     * @return array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>
     */
    public function busySlotsOn(DateTimeInterface $day, int $expertId, int $userId): array;

    public function find(int $reservationId): Reservation;

    /**
     * Insert a new reservation (in the Reserve state) or update an existing
     * one, and store its priced services. Returns the reservation with its id.
     */
    public function save(Reservation $reservation): Reservation;

    /**
     * Grant the best applicable discount to a just-booked reservation.
     */
    public function applyBestDiscount(Reservation $reservation): void;

    /**
     * Recalculate the discount a reservation already has against its new price.
     */
    public function recomputeDiscount(Reservation $reservation): void;
}
