<?php

namespace User\Application\Services;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\User;
use App\Services\DiscountService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DiscountManagementService
{
    public function __construct(private readonly DiscountService $discountService) {}

    /**
     * The authenticated customer's own manual discounts.
     *
     * @return Collection<int, Discount>
     */
    public function index(User $user): Collection
    {
        return Discount::query()
            ->active()
            ->where('type', DiscountType::Manual)
            ->where('user_id', $user->id)
            ->get();
    }

    /**
     * Discounts usable by the customer at a given hall today (their manual + active holidays).
     *
     * @return Collection<int, Discount>
     */
    public function available(int $hallId, User $user): Collection
    {
        return $this->discountService->applicable($hallId, $user->id, Carbon::now());
    }
}
