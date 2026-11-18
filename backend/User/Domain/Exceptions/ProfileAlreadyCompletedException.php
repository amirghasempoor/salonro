<?php

namespace User\Domain\Exceptions;

class ProfileAlreadyCompletedException extends UserException
{
    public static function forProfile(): self
    {
        return new self(__('messages.profile_already_completed'), 409);
    }
}
