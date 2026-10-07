<?php

namespace User\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use User\Application\Http\Requests\Discount\AvailableRequest;
use User\Application\Http\Resources\DiscountResource;
use User\Application\Services\DiscountManagementService;

class DiscountManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DiscountManagementService $discountManagementService) {}

    public function index(): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('user')->user();

        return $this->successResponse(
            DiscountResource::collection($this->discountManagementService->index($user))
        );
    }

    public function available(AvailableRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('user')->user();

        return $this->successResponse(
            DiscountResource::collection($this->discountManagementService->available((int) $request->hall_id, $user))
        );
    }

    public function show(Discount $discount): JsonResponse
    {
        return $this->successResponse(new DiscountResource($discount));
    }
}
