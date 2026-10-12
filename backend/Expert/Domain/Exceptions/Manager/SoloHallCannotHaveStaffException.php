<?php

namespace Expert\Domain\Exceptions\Manager;

use Expert\Domain\Exceptions\ExpertException;

class SoloHallCannotHaveStaffException extends ExpertException
{
    public static function forHall(): self
    {
        return new self(__('messages.solo_hall_cannot_have_staff'));
    }
}
