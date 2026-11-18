<?php

namespace User\Domain\Exceptions\Reservation;

use User\Domain\Exceptions\UserException;

class HallDoesNotOfferServiceException extends UserException
{
    public static function forService(int $serviceId): self
    {
        return new self(__('messages.hall_does_not_offer_service'));
    }
}
