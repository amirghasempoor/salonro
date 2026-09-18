<?php

namespace App\Services;

use App\Enums\DiscountAmountType;
use App\Enums\DiscountType;
use App\Models\Discount;
use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DiscountService
{
    /**
     * All discounts that apply to a booking context, regardless of base total.
     *
     * Matches the customer's own manual discounts (with remaining uses, in their
     * optional window) and the hall's active holiday discounts covering the date.
     *
     * @return Collection<int, Discount>
     */
    public function applicable(int $hallId, ?int $userId, CarbonInterface $date): Collection
    {
        $day = $date->toDateString();

        return Discount::query()
            ->active()
            ->where('hall_id', $hallId)
            ->where(function (Builder $query) use ($userId, $day) {
                $query->where(function (Builder $manual) use ($userId, $day) {
                    $manual->where('type', DiscountType::Manual)
                        ->whereNotNull('user_id')
                        ->where('user_id', $userId)
                        ->where(fn (Builder $w) => $w->whereNull('starts_at')->orWhereDate('starts_at', '<=', $day))
                        ->where(fn (Builder $w) => $w->whereNull('ends_at')->orWhereDate('ends_at', '>=', $day));
                })->orWhere(function (Builder $holiday) use ($day) {
                    $holiday->where('type', DiscountType::Holiday)
                        ->whereDate('starts_at', '<=', $day)
                        ->whereDate('ends_at', '>=', $day);
                });
            })
            ->get()
            ->reject(fn (Discount $discount) => $discount->type === DiscountType::Manual && ! $discount->hasRemainingUses())
            ->values();
    }

    /**
     * Resolve the single best discount for a booking, or null when none applies.
     *
     * Returns the applicable discount with the largest reduction (ties resolve to
     * the manual discount). Never combines discounts.
     *
     * @return array{discount: Discount, amount: int}|null
     */
    public function resolveBest(int $hallId, ?int $userId, CarbonInterface $date, int $baseTotal): ?array
    {
        if ($baseTotal <= 0) {
            return null;
        }

        $best = null;

        foreach ($this->applicable($hallId, $userId, $date) as $discount) {
            $amount = $this->reductionFor($discount, $baseTotal);

            if ($amount <= 0) {
                continue;
            }

            $isBetter = $best === null
                || $amount > $best['amount']
                || ($amount === $best['amount']
                    && $discount->type === DiscountType::Manual
                    && $best['discount']->type !== DiscountType::Manual);

            if ($isBetter) {
                $best = ['discount' => $discount, 'amount' => $amount];
            }
        }

        return $best;
    }

    /**
     * The Toman reduction a discount yields against a base total, clamped to the base.
     */
    public function reductionFor(Discount $discount, int $baseTotal): int
    {
        if ($baseTotal <= 0) {
            return 0;
        }

        return match ($discount->amount_type) {
            DiscountAmountType::Percentage => min((int) round($baseTotal * $discount->amount / 100), $baseTotal),
            DiscountAmountType::Fixed => min($discount->amount, $baseTotal),
        };
    }

    /**
     * Apply a resolved discount to a reservation inside the caller's transaction.
     *
     * Re-reads the discount under a row lock and re-checks its usage so concurrent
     * bookings cannot push a manual discount past its usage_limit.
     */
    public function apply(Reservation $reservation, Discount $discount, int $baseTotal): void
    {
        $locked = Discount::query()->lockForUpdate()->find($discount->id);

        if ($locked === null || ! $locked->is_active) {
            return;
        }

        if ($locked->type === DiscountType::Manual && ! $locked->hasRemainingUses()) {
            return;
        }

        $amount = $this->reductionFor($locked, $baseTotal);

        if ($amount <= 0) {
            return;
        }

        if ($locked->type === DiscountType::Manual) {
            $locked->increment('used_count');
        }

        $reservation->forceFill([
            'discount_id' => $locked->id,
            'discount_amount' => $amount,
            'total_price' => $baseTotal - $amount,
        ])->save();
    }

    /**
     * Recalculate an already-granted discount against a new base total (e.g. when a
     * reservation is edited), without granting new discounts or consuming extra uses.
     * Drops the discount if it is no longer active or no longer belongs to the hall.
     */
    public function recomputeExisting(Reservation $reservation, int $hallId, int $baseTotal): void
    {
        $discount = $reservation->discount;

        $applicable = $discount !== null
            && $discount->is_active
            && $discount->hall_id === $hallId;

        $amount = $applicable ? $this->reductionFor($discount, $baseTotal) : 0;

        $reservation->forceFill([
            'discount_id' => $amount > 0 ? $discount->id : null,
            'discount_amount' => $amount,
            'total_price' => $baseTotal - $amount,
        ])->save();
    }
}
