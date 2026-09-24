<?php

namespace Expert\Application\Policies;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Auth\Access\Response;

class ReservationPolicy
{
    /**
     * Hall-level abilities (list, create): the hall's owner or one of its staff.
     */
    public function forHall(Expert $expert, Hall $hall): bool
    {
        return $this->canAccessHall($expert, $hall);
    }

    /**
     * Reservation-level abilities reached through a hall: hall access, and the
     * reservation must actually belong to that hall (404 otherwise).
     */
    public function forReservation(Expert $expert, Hall $hall, Reservation $reservation): bool|Response
    {
        if (! $this->canAccessHall($expert, $hall)) {
            return false;
        }

        return $reservation->hall_id === $hall->id ? true : Response::denyAsNotFound();
    }

    /**
     * Reservation-level ability reached by reservation id alone: access to the
     * hall the reservation belongs to.
     */
    public function forReservationHall(Expert $expert, Reservation $reservation): bool
    {
        return $this->canAccessHall($expert, $reservation->hall);
    }

    private function canAccessHall(Expert $expert, Hall $hall): bool
    {
        return $hall->owner_id === $expert->id
            || $hall->experts()->where('experts.id', $expert->id)->exists();
    }
}
