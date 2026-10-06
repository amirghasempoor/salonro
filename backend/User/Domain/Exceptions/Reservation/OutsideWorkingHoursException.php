<?php

namespace User\Domain\Exceptions\Reservation;

use User\Domain\Exceptions\UserException;

class OutsideWorkingHoursException extends UserException
{
    public static function forSlot(): self
    {
        return new self(__('messages.outside_working_hours'));
    }
}
