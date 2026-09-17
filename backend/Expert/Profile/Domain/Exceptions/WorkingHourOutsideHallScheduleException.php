<?php

namespace Expert\Profile\Domain\Exceptions;

class WorkingHourOutsideHallScheduleException extends ProfileException
{
    public static function forDay(string $day): self
    {
        return new self(__('messages.working_hour_outside_hall_schedule'));
    }
}
