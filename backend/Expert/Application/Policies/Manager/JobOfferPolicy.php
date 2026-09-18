<?php

namespace Expert\Application\Policies\Manager;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;

class JobOfferPolicy
{
    public function forHall(Expert $manager, Hall $hall): bool
    {
        return $manager->id === $hall->owner_id;
    }

    public function forJobOffer(Expert $manager, JobOffer $jobOffer): bool
    {
        return $manager->id === $jobOffer->hall->owner_id;
    }

    public function forApplication(Expert $manager, JobOfferApplication $application): bool
    {
        return $manager->id === $application->jobOffer->hall->owner_id;
    }
}
