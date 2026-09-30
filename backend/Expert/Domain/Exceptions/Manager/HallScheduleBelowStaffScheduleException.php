<?php

namespace Expert\Domain\Exceptions\Manager;

use Expert\Domain\Exceptions\ExpertException;

class HallScheduleBelowStaffScheduleException extends ExpertException
{
    public static function forDay(string $day): self
    {
        return new self(__('messages.hall_schedule_below_staff_schedule'));
    }
}
