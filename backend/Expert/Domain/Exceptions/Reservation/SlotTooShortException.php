<?php

namespace Expert\Domain\Exceptions\Reservation;

use Expert\Domain\Exceptions\ExpertException;

class SlotTooShortException extends ExpertException
{
    public static function forSlot(): self
    {
        return new self(__('messages.slot_too_short'));
    }
}
