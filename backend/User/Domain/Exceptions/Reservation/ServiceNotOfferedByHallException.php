<?php

namespace User\Domain\Exceptions\Reservation;

use User\Domain\Exceptions\UserException;

class ServiceNotOfferedByHallException extends UserException
{
    public static function forService(int $serviceId): self
    {
        return new self(__('messages.service_not_offered_by_hall'));
    }
}
