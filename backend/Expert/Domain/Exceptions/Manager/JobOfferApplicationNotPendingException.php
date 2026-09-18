<?php

namespace Expert\Domain\Exceptions\Manager;

use Expert\Domain\Exceptions\ExpertException;

class JobOfferApplicationNotPendingException extends ExpertException
{
    public static function forApplication(): self
    {
        return new self(__('messages.job_offer_application_not_pending'));
    }
}
