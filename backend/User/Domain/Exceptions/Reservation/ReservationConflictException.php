<?php

namespace User\Domain\Exceptions\Reservation;

use User\Domain\Exceptions\UserException;

class ReservationConflictException extends UserException
{
    public static function forSlot(): self
    {
        return new self(__('messages.reservation_conflict'));
    }
}
