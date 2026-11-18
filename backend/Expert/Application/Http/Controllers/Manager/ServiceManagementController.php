<?php

namespace Expert\Application\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Expert\Application\Http\Requests\Manager\ServiceCategory\StoreRequest;
use Expert\Application\Http\Requests\Manager\ServiceCategory\UpdateRequest;
use Expert\Application\Services\Manager\ServiceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Shared\Traits\ApiResponse;

class ServiceManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ServiceManagementService $serviceManagementService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->serviceManagementService->index($request));
    }

    public function list(): JsonResponse
    {
        return response()->json($this->serviceManagementService->list());
    }

    public function store(StoreRequest $request): JsonResponse
    {
        $this->serviceManagementService->store($request);

        return $this->successResponse();
    }

    public function show(Service $category): JsonResponse
    {
        return $this->successResponse($category);
    }

    public function update(UpdateRequest $request, Service $category): JsonResponse
    {
        $this->serviceManagementService->update($request, $category);

        return $this->successResponse();
    }

    public function destroy(Service $category): JsonResponse
    {
        if ($this->serviceManagementService->isInUse($category)) {
            return $this->errorResponse(__('messages.service_in_use'), 'conflict', 409);
        }

        $this->serviceManagementService->destroy($category);

        return $this->successResponse();
    }
}
