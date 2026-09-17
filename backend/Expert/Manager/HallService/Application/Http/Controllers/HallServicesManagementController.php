<?php

namespace Expert\Manager\HallService\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\HallService;
use App\Traits\ApiResponse;
use Expert\Manager\HallService\Application\Http\Requests\StoreRequest;
use Expert\Manager\HallService\Application\Http\Requests\UpdateRequest;
use Expert\Manager\HallService\Application\Http\Resources\HallServiceResource;
use Expert\Manager\HallService\Application\Services\HallServiceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HallServicesManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly HallServiceManagementService $hallServiceManagementService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Hall $hall): JsonResponse
    {
        return response()->json($this->hallServiceManagementService->index($request, $hall));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $hallService = $this->hallServiceManagementService->store($request, $hall);

        return $this->successResponse([
            'hall_service_id' => $hallService->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(HallService $hallService): JsonResponse
    {
        return $this->successResponse(new HallServiceResource($hallService->load('service')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, HallService $hallService): JsonResponse
    {
        $this->hallServiceManagementService->update($request, $hallService);

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(HallService $hallService): JsonResponse
    {
        $this->hallServiceManagementService->destroy($hallService);

        return $this->successResponse();
    }
}
