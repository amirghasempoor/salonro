<?php

namespace Expert\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\ExpertService;
use Expert\Application\Http\Requests\MyService\StoreRequest;
use Expert\Application\Http\Requests\MyService\UpdateRequest;
use Expert\Application\Http\Resources\MyServiceResource;
use Expert\Application\Services\MyServiceManagementService;
use Expert\Domain\Actions\DefineExpertServiceAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Shared\Traits\ApiResponse;
use Throwable;

class MyServiceController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly MyServiceManagementService $myServiceManagementService) {}

    /**
     * The authenticated expert's own priced services at this hall.
     */
    public function index(int $hall): JsonResponse
    {
        return $this->successResponse(
            MyServiceResource::collection(
                $this->myServiceManagementService->index(Auth::guard('expert')->user(), $hall)
            )
        );
    }

    /**
     * Set the price and duration for one or more services at this hall.
     *
     * @throws Throwable
     */
    public function store(StoreRequest $request, int $hall, DefineExpertServiceAction $action): JsonResponse
    {
        $services = $action->execute(Auth::guard('expert')->user(), $hall, $request->toDto());

        return $this->successResponse(MyServiceResource::collection($services));
    }

    /**
     * Display the specified resource.
     */
    public function show(ExpertService $expertService): JsonResponse
    {
        return $this->successResponse(new MyServiceResource($expertService->load('service')));
    }

    /**
     * Update the price and duration of one of the expert's own services.
     */
    public function update(UpdateRequest $request, ExpertService $expertService): JsonResponse
    {
        $this->myServiceManagementService->update($request, $expertService);

        return $this->successResponse();
    }

    /**
     * Stop offering a service.
     */
    public function destroy(ExpertService $expertService): JsonResponse
    {
        $this->myServiceManagementService->destroy($expertService);

        return $this->successResponse();
    }
}
