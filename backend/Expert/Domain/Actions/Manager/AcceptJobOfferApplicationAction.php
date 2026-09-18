<?php

namespace Expert\Domain\Actions\Manager;

use App\Models\JobOfferApplication;
use Expert\Domain\Exceptions\Manager\JobOfferApplicationNotPendingException;
use Expert\Domain\Repositories\Manager\JobOfferApplicationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Accept a job application and onboard the expert to the hall (role, hall
 * membership) if they are not already an active member there.
 */
class AcceptJobOfferApplicationAction
{
    public function __construct(
        private readonly JobOfferApplicationRepositoryInterface $repository,
    ) {}

    /**
     * @throws JobOfferApplicationNotPendingException when the application was already accepted or rejected
     * @throws Throwable
     */
    public function execute(JobOfferApplication $application): void
    {
        throw_unless(
            $application->status === JobOfferApplication::STATUS_PENDING,
            JobOfferApplicationNotPendingException::forApplication()
        );

        DB::transaction(function () use ($application) {
            $this->repository->markAccepted($application);

            $hall = $application->jobOffer->hall;
            $expert = $application->expert;

            if (! $this->repository->isActiveMember($hall, $expert)) {
                $this->repository->onboard($hall, $expert);
            }
        });
    }
}
