<?php

namespace Expert\Application\Persistence\Manager;

use App\Enums\Roles;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\JobOfferApplication;
use Expert\Domain\Repositories\Manager\JobOfferApplicationRepositoryInterface;

class EloquentJobOfferApplicationRepository implements JobOfferApplicationRepositoryInterface
{
    public function markAccepted(JobOfferApplication $application): void
    {
        $application->update(['status' => JobOfferApplication::STATUS_ACCEPTED]);
    }

    public function isActiveMember(Hall $hall, Expert $expert): bool
    {
        return $hall->experts()->where('experts.id', $expert->id)->exists();
    }

    public function onboard(Hall $hall, Expert $expert): void
    {
        $expert->assignRole(Roles::Expert->value);
        $hall->experts()->attach($expert->id, ['joined_at' => now()]);
    }
}
