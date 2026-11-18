<?php

namespace Expert\Domain\Exceptions\Manager;

use Expert\Domain\Exceptions\ExpertException;

class UpcomingReservationsException extends ExpertException
{
    public static function forHall(): self
    {
        return new self(__('messages.hall_has_upcoming_reservations'), 409);
    }

    public static function forExpert(): self
    {
        return new self(__('messages.expert_has_upcoming_reservations'), 409);
    }
}
