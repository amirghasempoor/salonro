<?php

namespace Expert\Domain\Exceptions;

class ExpertNotAssignedToHallException extends ExpertException
{
    public static function forHall(int $hallId): self
    {
        return new self(__('messages.expert_not_in_hall'));
    }
}
