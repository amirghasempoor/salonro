<?php

namespace Expert\Domain\Exceptions\Reservation;

use Expert\Domain\Exceptions\ExpertException;

class OutsideWorkingHoursException extends ExpertException
{
    public static function forSlot(): self
    {
        return new self(__('messages.outside_working_hours'));
    }
}
