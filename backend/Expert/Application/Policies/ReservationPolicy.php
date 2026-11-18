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
     * Reservation-level abilities reached through a hall: hall access, the
     * reservation must actually belong to that hall (404 otherwise), and it must
     * be one the expert may manage.
     */
    public function forReservation(Expert $expert, Hall $hall, Reservation $reservation): bool|Response
    {
        if (! $this->canAccessHall($expert, $hall)) {
            return false;
        }

        if ($reservation->hall_id !== $hall->id) {
            return Response::denyAsNotFound();
        }

        return $this->canManage($expert, $hall, $reservation);
    }

    /**
     * Reservation-level ability reached by reservation id alone: access to the
     * hall the reservation belongs to, and it must be one the expert may manage.
     */
    public function forReservationHall(Expert $expert, Reservation $reservation): bool
    {
        $hall = $reservation->hall;

        return $this->canAccessHall($expert, $hall) && $this->canManage($expert, $hall, $reservation);
    }

    /**
     * The hall's owner manages every reservation in it; any other staff member
     * only her own — the same rule the reservation list applies.
     */
    private function canManage(Expert $expert, Hall $hall, Reservation $reservation): bool
    {
        return $hall->owner_id === $expert->id || $reservation->expert_id === $expert->id;
    }

    private function canAccessHall(Expert $expert, Hall $hall): bool
    {
        return $hall->owner_id === $expert->id
            || $hall->experts()->where('experts.id', $expert->id)->exists();
    }
}
