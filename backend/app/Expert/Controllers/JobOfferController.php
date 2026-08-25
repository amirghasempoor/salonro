<?php

namespace App\Expert\Controllers;

use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use App\Models\JobOfferApplication;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobOfferController extends Controller
{
    use ApiResponse;

    public function index(Request $request): JsonResponse
    {
        $data = DataTableFacade::run(
            JobOffer::query()->where('is_active', true)->with('hall', 'profession'),
            $request,
            allowedFilters: ['*'],
            allowedRelations: ['hall', 'profession'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'hall_id', 'hall_name', 'profession_id', 'profession_name', 'description',
                'created_at', 'province_id', 'province_name', 'city_id', 'city_name',
            ],
        );

        return response()->json($data);
    }

    public function show(JobOffer $jobOffer): JsonResponse
    {
        return $this->successResponse($jobOffer->load('hall', 'expert', 'profession'));
    }

    public function apply(JobOffer $jobOffer): JsonResponse
    {
        try {
            $expert = Auth::guard('expert')->user();

            $existing = JobOfferApplication::query()->where('job_offer_id', $jobOffer->id)
                ->where('expert_id', $expert->id)
                ->first();

            if ($existing) {
                return $this->errorResponse(__('messages.already_applied'));
            }

            $jobOffer->applications()->create([
                'expert_id' => $expert->id,
            ]);

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function myApplications(): JsonResponse
    {
        $applications = Auth::guard('expert')->user()
            ->jobApplications()
            ->with('jobOffer.hall', 'jobOffer.profession')
            ->get();

        return $this->successResponse($applications);
    }
}
