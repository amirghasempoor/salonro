<?php

namespace Expert\Application\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\Manager\JobOffer\StoreRequest;
use Expert\Application\Http\Requests\Manager\JobOffer\UpdateRequest;
use Expert\Application\Http\Resources\Manager\JobOfferApplicationResource;
use Expert\Application\Http\Resources\Manager\JobOfferResource;
use Expert\Application\Services\Manager\JobOfferManagementService;
use Expert\Domain\Actions\Manager\AcceptJobOfferApplicationAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class JobOfferController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly JobOfferManagementService $jobOfferManagementService,
    ) {}

    public function index(Request $request, Hall $hall): JsonResponse
    {
        return response()->json($this->jobOfferManagementService->index($request, $hall));
    }

    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $this->jobOfferManagementService->store($request, $hall);

        return $this->successResponse();
    }

    public function show(JobOffer $jobOffer): JsonResponse
    {
        return $this->successResponse(new JobOfferResource($jobOffer));
    }

    public function update(UpdateRequest $request, JobOffer $jobOffer): JsonResponse
    {
        $this->jobOfferManagementService->update($request, $jobOffer);

        return $this->successResponse();
    }

    public function destroy(JobOffer $jobOffer): JsonResponse
    {
        $this->jobOfferManagementService->destroy($jobOffer);

        return $this->successResponse();
    }

    public function applications(JobOffer $jobOffer): JsonResponse
    {
        $applications = $this->jobOfferManagementService->applications($jobOffer)
            ->toResourceCollection(JobOfferApplicationResource::class);

        return $this->successResponse($applications);
    }

    /**
     * @throws Throwable
     */
    public function acceptApplication(JobOfferApplication $application, AcceptJobOfferApplicationAction $action): JsonResponse
    {
        $action->execute($application);

        return $this->successResponse();
    }

    public function rejectApplication(JobOfferApplication $application): JsonResponse
    {
        $this->jobOfferManagementService->rejectApplication($application);

        return $this->successResponse();
    }
}
