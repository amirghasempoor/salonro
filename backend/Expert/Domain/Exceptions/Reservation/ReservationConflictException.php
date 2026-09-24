<?php

namespace Expert\Domain\Exceptions\Reservation;

use Expert\Domain\Exceptions\ExpertException;

class ReservationConflictException extends ExpertException
{
    public static function forSlot(): self
    {
        return new self(__('messages.reservation_conflict'));
    }
}
