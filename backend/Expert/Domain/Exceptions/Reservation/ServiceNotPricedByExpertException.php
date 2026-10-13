<?php

namespace Expert\Domain\Exceptions\Reservation;

use Expert\Domain\Exceptions\ExpertException;

class ServiceNotPricedByExpertException extends ExpertException
{
    public static function forService(int $serviceId): self
    {
        return new self(__('messages.service_not_priced_by_expert'));
    }
}
