<?php

namespace Expert\Application\Policies\Manager;

use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;

class DiscountPolicy
{
    /**
     * Hall-level abilities (list, create): the hall's owning manager only.
     */
    public function forHall(Expert $expert, Hall $hall): bool
    {
        return $expert->id === $hall->owner_id;
    }

    /**
     * Discount-level abilities: the hall's owner, and the discount must actually
     * belong to that hall (so a manager can't reach another hall's discount
     * through their own hall id).
     */
    public function forDiscount(Expert $expert, Hall $hall, Discount $discount): bool
    {
        return $expert->id === $hall->owner_id && $discount->hall_id === $hall->id;
    }
}
