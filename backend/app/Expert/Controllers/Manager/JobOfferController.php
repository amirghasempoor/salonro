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
use App\Models\Profession;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class JobOfferController extends Controller
{
    use ApiResponse;

    public function index(Request $request, Hall $hall): JsonResponse
    {
        $data = DataTableFacade::run(
            $hall->jobOffers(),
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: ['id', 'hall_id', 'hall_name', 'profession_id', 'description', 'is_active', 'created_at'],
        );

        return response()->json($data);
    }

    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        try {
            $profession = Profession::query()->find($request->profession_id);

            JobOffer::query()->create([
                'hall_id' => $hall->id,
                'hall_name' => $hall->name,
                'expert_id' => $hall->owner_id,
                'profession_id' => $profession->id,
                'profession_name' => $profession->name,
                'description' => $request->description,
                'province_id' => $hall->province_id,
                'province_name' => $hall->province_name,
                'city_id' => $hall->city_id,
                'city_name' => $hall->city_name,
            ]);

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(JobOffer $jobOffer): JsonResponse
    {
        return $this->successResponse(new JobOfferResource($jobOffer));
    }

    public function update(UpdateRequest $request, JobOffer $jobOffer): JsonResponse
    {
        try {
            $jobOffer->update($request->validated());

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(JobOffer $jobOffer): JsonResponse
    {
        try {
            $jobOffer->delete();

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * @throws Throwable
     */
    public function applications(JobOffer $jobOffer): JsonResponse
    {
        $applications = $jobOffer->applications()
            ->with('expert')
            ->get()
            ->toResourceCollection(JobOfferApplicationResource::class);

        return $this->successResponse($applications);
    }

    public function acceptApplication(JobOfferApplication $application): JsonResponse
    {
        try {
            $application->update(['status' => JobOfferApplication::STATUS_ACCEPTED]);

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function rejectApplication(JobOfferApplication $application): JsonResponse
    {
        try {
            $application->update(['status' => JobOfferApplication::STATUS_REJECTED]);

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
