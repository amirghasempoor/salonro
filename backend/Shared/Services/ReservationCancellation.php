<?php

namespace Shared\Services;

use App\Enums\ReservationStates;
use App\Models\Reservation;
use Illuminate\Support\Facades\DB;
use Shared\Exceptions\ReservationNotChangeableException;
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

        // Only a booking that is still reserved and has not ended can be cancelled:
        // a finished one is history and must stay as it was.
        throw_unless(
            $reservation->state_id === ReservationStates::Reserve->value && $reservation->finish_time->isFuture(),
            ReservationNotChangeableException::forCancel(),
        );

        DB::transaction(function () use ($reservation) {
            $this->discountService->release($reservation);

            $reservation->forceFill([
                'state_id' => ReservationStates::Cancel->value,
                'state_name' => ReservationStates::Cancel->label(),
            ])->save();
        });
    }
}
