<?php

namespace Expert\Application\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\Manager\Discount\StoreRequest;
use Expert\Application\Http\Requests\Manager\Discount\UpdateRequest;
use Expert\Application\Http\Resources\Manager\DiscountDetailsResource;
use Expert\Application\Services\Manager\DiscountManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DiscountManagementService $discountManagementService) {}

    public function index(Request $request, Hall $hall): JsonResponse
    {
        return response()->json($this->discountManagementService->index($request, $hall));
    }

    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $discount = $this->discountManagementService->store($request, $hall, Auth::guard('expert')->user());

        return $this->successResponse([
            'discount_id' => $discount->id,
        ]);
    }

    public function show(Hall $hall, Discount $discount): JsonResponse
    {
        return $this->successResponse(
            new DiscountDetailsResource($this->discountManagementService->show($discount))
        );
    }

    public function update(UpdateRequest $request, Hall $hall, Discount $discount): JsonResponse
    {
        $this->discountManagementService->update($request, $discount);

        return $this->successResponse();
    }

    public function destroy(Hall $hall, Discount $discount): JsonResponse
    {
        $this->discountManagementService->destroy($discount);

        return $this->successResponse();
    }
}
