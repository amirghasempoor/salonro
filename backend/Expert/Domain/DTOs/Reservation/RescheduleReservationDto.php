<?php

namespace Expert\Domain\DTOs\Reservation;

use DateTimeImmutable;

final readonly class RescheduleReservationDto
{
    /**
     * @param  array<int, int>  $serviceIds
     */
    public function __construct(
        public array $serviceIds,
        public DateTimeImmutable $start,
        public DateTimeImmutable $finish,
    ) {}
}
