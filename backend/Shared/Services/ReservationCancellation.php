<?php

namespace Shared\Services;

use App\Enums\ReservationStates;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Cancelling keeps the reservation as a record (customer history, the hall's
 * books) but frees its slot and gives back any discount use it consumed.
 */
class ReservationCancellation
{
    public function __construct(private readonly DiscountService $discountService) {}

    /**
     * @throws Throwable
     */
    public function execute(Reservation $reservation): void
    {
        if ($reservation->state_id === ReservationStates::Cancel->value) {
            return;
        }

        DB::transaction(function () use ($reservation) {
            $this->discountService->release($reservation);

            $reservation->forceFill([
                'state_id' => ReservationStates::Cancel->value,
                'state_name' => ReservationStates::Cancel->label(),
            ])->save();
        });
    }
}
