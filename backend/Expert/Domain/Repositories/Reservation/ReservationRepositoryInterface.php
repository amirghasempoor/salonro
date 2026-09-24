<?php

namespace Expert\Domain\Repositories\Reservation;

use DateTimeInterface;
use Expert\Domain\Entities\Hall;
use Expert\Domain\Entities\Reservation;

interface ReservationRepositoryInterface
{
    /**
     * The customer with this phone number, created when they do not exist yet.
     *
     * @return array{id: int, name: string}
     */
    public function findOrCreateCustomer(string $phoneNumber, string $firstName, string $lastName): array;

    public function expertName(int $expertId): string;

    /**
     * The hall with the services it offers.
     */
    public function findHall(int $hallId): Hall;

    /**
     * Active (not cancelled) reservations of the expert or the customer on the
     * given day. The entity decides which of them actually conflict.
     *
     * @return array<int, array{id: int, start: DateTimeInterface, finish: DateTimeInterface}>
     */
    public function busySlotsOn(DateTimeInterface $day, int $expertId, int $userId): array;

    public function find(int $reservationId): Reservation;

    /**
     * Insert a new reservation (in the Reserve state) or update an existing one,
     * and store its priced services. Returns the reservation with its id.
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
