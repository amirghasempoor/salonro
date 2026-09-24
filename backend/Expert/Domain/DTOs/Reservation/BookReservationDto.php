<?php

namespace Expert\Domain\DTOs\Reservation;

use DateTimeImmutable;

final readonly class BookReservationDto
{
    /**
     * @param  array<int, int>  $serviceIds
     */
    public function __construct(
        public int $hallId,
        public int $expertId,
        public string $phoneNumber,
        public string $firstName,
        public string $lastName,
        public array $serviceIds,
        public DateTimeImmutable $start,
        public DateTimeImmutable $finish,
    ) {}
}
