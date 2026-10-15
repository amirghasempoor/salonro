<?php

namespace User\Domain\Exceptions\Reservation;

use User\Domain\Exceptions\UserException;

class ServiceNotPricedByExpertException extends UserException
{
    public static function forService(int $serviceId): self
    {
        return new self(__('messages.service_not_priced_by_expert'));
    }
}
