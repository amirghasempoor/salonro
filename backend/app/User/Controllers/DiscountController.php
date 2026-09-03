<?php

namespace App\User\Controllers;

use App\Enums\DiscountType;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Services\DiscountService;
use App\Traits\ApiResponse;
use App\User\Resources\DiscountResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class DiscountController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly DiscountService $discountService,
    ) {}

    /**
     * The authenticated customer's own manual discounts.
     */
    public function index(): JsonResponse
    {
        $discounts = Discount::query()
            ->active()
            ->where('type', DiscountType::Manual)
            ->where('user_id', Auth::guard('user')->id())
            ->get();

        return $this->successResponse(DiscountResource::collection($discounts));
    }

    /**
     * Discounts usable by the customer at a given hall today (their manual + active holidays).
     */
    public function available(Request $request): JsonResponse
    {
        $request->validate([
            'hall_id' => 'required|integer|exists:halls,id',
        ]);

        $discounts = $this->discountService->applicable(
            (int) $request->hall_id,
            Auth::guard('user')->id(),
            Carbon::now(),
        );

        return $this->successResponse(DiscountResource::collection($discounts));
    }

    /**
     * A single discount, scoped to the customer's own manual discount or a public holiday.
     */
    public function show(Discount $discount): JsonResponse
    {
        $isOwnManual = $discount->type === DiscountType::Manual
            && $discount->user_id === Auth::guard('user')->id();

        $isPublicHoliday = $discount->type === DiscountType::Holiday && $discount->is_active;

        abort_unless($isOwnManual || $isPublicHoliday, 403);

        return $this->successResponse(new DiscountResource($discount));
    }
}
