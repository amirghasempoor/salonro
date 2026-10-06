<?php

namespace User\Domain\DTOs\Reservation;

use DateTimeImmutable;

final readonly class RescheduleReservationDto
{
    /**
     * @param  array<int, int>  $serviceIds
     */
    public function __construct(
        public int $hallId,
        public int $expertId,
        public array $serviceIds,
        public DateTimeImmutable $start,
        public DateTimeImmutable $finish,
    ) {}
}
