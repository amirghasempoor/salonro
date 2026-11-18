<?php

namespace Expert\Domain\Exceptions\Manager;

use Expert\Domain\Exceptions\ExpertException;

class ExpertAlreadyInHallException extends ExpertException
{
    public static function forHall(): self
    {
        return new self(__('messages.expert_already_in_hall'), 409);
    }
}
