<?php

namespace Expert\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\JobOffer;
use App\Traits\ApiResponse;
use Expert\Application\Services\JobApplicationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly JobApplicationService $jobApplicationService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->jobApplicationService->index($request));
    }

    public function show(JobOffer $jobOffer): JsonResponse
    {
        return $this->successResponse($jobOffer);
    }

    public function apply(JobOffer $jobOffer): JsonResponse
    {
        $applied = $this->jobApplicationService->apply($jobOffer, Auth::guard('expert')->user());

        if (! $applied) {
            return $this->errorResponse(__('messages.already_applied'));
        }

        return $this->successResponse();
    }

    public function myApplications(): JsonResponse
    {
        return $this->successResponse(
            $this->jobApplicationService->myApplications(Auth::guard('expert')->user())
        );
    }
}
