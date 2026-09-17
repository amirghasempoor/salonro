<?php

namespace Expert\Manager\Staff\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Expert\Manager\Staff\Application\Http\Requests\StoreRequest;
use Expert\Manager\Staff\Application\Http\Requests\UpdateRequest;
use Expert\Manager\Staff\Application\Http\Resources\ExpertDetailsResource;
use Expert\Manager\Staff\Application\Services\StaffManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class ExpertManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly StaffManagementService $staffManagementService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Hall $hall): JsonResponse
    {
        return response()->json($this->staffManagementService->index($request, $hall));
    }

    public function staffList(Hall $hall): JsonResponse
    {
        return response()->json($hall->experts()->get(['id', 'avatar']));
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $this->staffManagementService->store($request, $hall);

        return $this->successResponse();
    }

    /**
     * Display the specified resource.
     */
    public function show(Expert $expert): JsonResponse
    {
        return $this->successResponse(new ExpertDetailsResource($expert));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Hall $hall, Expert $expert): JsonResponse
    {
        $this->staffManagementService->update($request, $expert);

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     * @throws Throwable
     */
    public function destroy(Hall $hall, Expert $expert): JsonResponse
    {
        $this->staffManagementService->destroy($expert);

        return $this->successResponse();
    }
}
