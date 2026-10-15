<?php

namespace Expert\Domain\Exceptions;

class HallDoesNotOfferServiceException extends ExpertException
{
    public static function forService(int $serviceId): self
    {
        return new self(__('messages.hall_does_not_offer_service'));
    }
}
