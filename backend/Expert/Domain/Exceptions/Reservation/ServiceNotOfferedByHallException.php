<?php

namespace Expert\Domain\Exceptions\Reservation;

use Expert\Domain\Exceptions\ExpertException;

class ServiceNotOfferedByHallException extends ExpertException
{
    public static function forService(int $serviceId): self
    {
        return new self(__('messages.service_not_offered_by_hall'));
    }
}
