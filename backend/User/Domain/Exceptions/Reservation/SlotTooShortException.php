<?php

namespace User\Domain\Exceptions\Reservation;

use User\Domain\Exceptions\UserException;

class SlotTooShortException extends UserException
{
    public static function forSlot(): self
    {
        return new self(__('messages.slot_too_short'));
    }
}
