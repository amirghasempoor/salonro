<?php

namespace Expert\Domain\Repositories\Manager;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\JobOfferApplication;

interface JobOfferApplicationRepositoryInterface
{
    public function markAccepted(JobOfferApplication $application): void;

    public function isActiveMember(Hall $hall, Expert $expert): bool;

    public function onboard(Hall $hall, Expert $expert): void;
}
