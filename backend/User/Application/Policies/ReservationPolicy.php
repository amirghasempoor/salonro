<?php

namespace User\Application\Policies;

use App\Models\Reservation;
use App\Models\User;

class ReservationPolicy
{
    /**
     * Only the customer the reservation was made for may view, reschedule or
     * cancel it.
     */
    public function owns(User $user, Reservation $reservation): bool
    {
        return $reservation->user_id === $user->id;
    }
}
