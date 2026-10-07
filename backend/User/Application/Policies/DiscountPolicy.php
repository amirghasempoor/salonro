<?php

namespace User\Application\Policies;

use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\User;

class DiscountPolicy
{
    /**
     * A customer may view their own manual discount, or any active public holiday discount.
     */
    public function view(User $user, Discount $discount): bool
    {
        $isOwnManual = $discount->type === DiscountType::Manual && $discount->user_id === $user->id;

        $isPublicHoliday = $discount->type === DiscountType::Holiday && $discount->is_active;

        return $isOwnManual || $isPublicHoliday;
    }
}
