<?php

namespace App\Expert\Controllers\Manager;

use App\Expert\Requests\Manager\JobOffer\StoreRequest;
use App\Expert\Requests\Manager\JobOffer\UpdateRequest;
use App\Expert\Resources\JobOfferApplicationResource;
use App\Expert\Resources\JobOfferResource;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOfferController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Hall $hall): JsonResponse
    {
        $data = DataTableFacade::run(
            $hall->jobOffers(),
            $request,
            allowedFilters: ['*'],
            allowedRelations: ['profession', 'applications', 'applications.expert'],
            allowedSortings: ['*'],
            allowedSelects: ['id', 'profession_id', 'description', 'is_active', 'created_at'],
        );

        return response()->json($data);
    }

    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        try {
            $hall->jobOffers()->create([
                'expert_id' => Auth::guard('expert')->user()->id,
                'profession_id' => $request->profession_id,
                'description' => $request->description,
            ]);

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(JobOffer $jobOffer): JsonResponse
    {
        return $this->successResponse(new JobOfferResource($jobOffer->load('profession', 'applications.expert')));
    }

    public function update(UpdateRequest $request, JobOffer $jobOffer): JsonResponse
    {
        try {
            $jobOffer->update($request->validated());

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(JobOffer $jobOffer): JsonResponse
    {
        try {
            $jobOffer->delete();

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function applications(JobOffer $jobOffer): JsonResponse
    {
        $applications = $jobOffer->applications()
            ->with('expert')
            ->get()
            ->map(fn (JobOfferApplication $app) => new JobOfferApplicationResource($app));

        return $this->successResponse($applications);
    }

    public function acceptApplication(JobOfferApplication $application): JsonResponse
    {
        try {
            $application->update(['status' => JobOfferApplication::STATUS_ACCEPTED]);

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function rejectApplication(JobOfferApplication $application): JsonResponse
    {
        try {
            $application->update(['status' => JobOfferApplication::STATUS_REJECTED]);

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
