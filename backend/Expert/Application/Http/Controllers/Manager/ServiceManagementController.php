<?php

namespace Expert\Application\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\Manager\ServiceCategory\StoreRequest;
use Expert\Application\Http\Requests\Manager\ServiceCategory\UpdateRequest;
use Expert\Application\Services\Manager\ServiceManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

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
        try {
            $this->serviceManagementService->store($request);

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function show(Service $category): JsonResponse
    {
        return $this->successResponse($category);
    }

    public function update(UpdateRequest $request, Service $category): JsonResponse
    {
        try {
            $this->serviceManagementService->update($request, $category);

            return $this->successResponse();
        } catch (Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(Service $category): JsonResponse
    {
        $this->serviceManagementService->destroy($category);

        return $this->successResponse();
    }
}
