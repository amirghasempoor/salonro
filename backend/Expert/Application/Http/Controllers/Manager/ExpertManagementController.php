<?php

namespace Expert\Application\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\Manager\Staff\StoreRequest;
use Expert\Application\Http\Requests\Manager\Staff\UpdateRequest;
use Expert\Application\Http\Resources\Manager\ExpertDetailsResource;
use Expert\Application\Services\Manager\StaffManagementService;
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
        return response()->json($hall->experts()->get(['experts.id', 'avatar']));
    }

    /**
     * Store a newly created resource in storage.
     *
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
     *
     * @throws Throwable
     */
    public function destroy(Hall $hall, Expert $expert): JsonResponse
    {
        $this->staffManagementService->destroy($expert);

        return $this->successResponse();
    }
}
