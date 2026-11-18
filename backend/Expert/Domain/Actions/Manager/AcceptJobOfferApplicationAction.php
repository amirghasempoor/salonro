<?php

namespace Expert\Domain\Actions\Manager;

use App\Models\JobOfferApplication;
use Expert\Domain\Exceptions\Manager\JobOfferApplicationNotPendingException;
use Expert\Domain\Exceptions\Manager\SoloHallCannotHaveStaffException;
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

        $hall = $application->jobOffer->hall;

        // A solo hall belongs to one expert; an old offer must not bring staff in.
        throw_if($hall->is_solo, SoloHallCannotHaveStaffException::forHall());

        DB::transaction(function () use ($application, $hall) {
            $this->repository->markAccepted($application);

            $expert = $application->expert;

            if (! $this->repository->isActiveMember($hall, $expert)) {
                $this->repository->onboard($hall, $expert);
            }
        });
    }
}
