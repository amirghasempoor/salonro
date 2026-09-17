<?php

namespace Expert\Manager\Hall\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Expert\Manager\Hall\Application\Http\Requests\StoreRequest;
use Expert\Manager\Hall\Application\Http\Requests\UpdateRequest;
use Expert\Manager\Hall\Application\Services\HallManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class HallManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly HallManagementService $hallManagementService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->hallManagementService->index($request));
    }

    /**
     * Store a newly created resource in storage.
     * @throws Throwable
     */
    public function store(StoreRequest $request): JsonResponse
    {
        $hall = $this->hallManagementService->store($request);

        return $this->successResponse([
            'hall_id' => $hall->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Hall $hall): JsonResponse
    {
        return $this->successResponse($hall->load('services'));
    }

    /**
     * Update the specified resource in storage.
     * @throws Throwable
     */
    public function update(UpdateRequest $request, Hall $hall): JsonResponse
    {
        $this->hallManagementService->update($request, $hall);

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     * @throws Throwable
     */
    public function destroy(Hall $hall): JsonResponse
    {
        $this->hallManagementService->destroy($hall);

        return $this->successResponse();
    }

    public function services(Hall $hall): JsonResponse
    {
        return $this->successResponse($hall->services()->get(['services.id', 'sub_cat_name']));
    }
}
