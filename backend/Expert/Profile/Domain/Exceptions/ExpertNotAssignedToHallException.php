<?php

namespace Expert\Profile\Domain\Exceptions;

class ExpertNotAssignedToHallException extends ProfileException
{
    public static function forHall(int $hallId): self
    {
        return new self(__('messages.expert_not_in_hall'));
    }
}
