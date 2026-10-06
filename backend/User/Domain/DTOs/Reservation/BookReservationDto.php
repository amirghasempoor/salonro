<?php

namespace User\Domain\DTOs\Reservation;

use DateTimeImmutable;

final readonly class BookReservationDto
{
    /**
     * @param  array<int, int>  $serviceIds
     */
    public function __construct(
        public int $hallId,
        public int $expertId,
        public int $userId,
        public string $userName,
        public array $serviceIds,
        public DateTimeImmutable $start,
        public DateTimeImmutable $finish,
    ) {}
}
