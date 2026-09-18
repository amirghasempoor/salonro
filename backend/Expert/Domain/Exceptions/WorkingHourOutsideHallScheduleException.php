<?php

namespace Expert\Domain\Exceptions;

class WorkingHourOutsideHallScheduleException extends ExpertException
{
    public static function forDay(string $day): self
    {
        return new self(__('messages.working_hour_outside_hall_schedule'));
    }
}
