<?php

namespace App\Traits;

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\Reservation;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

trait EnsuresReservationAvailability
{
    /**
     * Ensure a reservation's expert, hall and time range are actually bookable.
     *
     * Throws when the slot falls outside the expert's defined working hours at the
     * hall, or when the expert or the user already has an overlapping reservation.
     *
     * @throws Throwable
     */
    protected function ensureReservationIsAvailable(
        Expert $expert,
        int $hallId,
        int $userId,
        CarbonInterface $start,
        CarbonInterface $finish,
        ?int $ignoreReservationId = null,
    ): void {
        $this->assertWithinWorkingHours($expert, $hallId, $start, $finish);
        $this->assertNoConflict($expert->id, $userId, $start, $finish, $ignoreReservationId);
    }

    /**
     * @throws Throwable
     */
    private function assertWithinWorkingHours(Expert $expert, int $hallId, CarbonInterface $start, CarbonInterface $finish): void
    {
        if (! $start->isSameDay($finish)) {
            throw new \RuntimeException(__('messages.outside_working_hours'));
        }

        $fits = $expert->workingHoursAtHall($hallId)
            ->where('day', strtolower($start->format('D')))
            ->where('from', '<=', $start->format('H:i:s'))
            ->where('to', '>=', $finish->format('H:i:s'))
            ->exists();

        throw_unless($fits, new \RuntimeException(__('messages.outside_working_hours')));
    }

    /**
     * @throws Throwable
     */
    private function assertNoConflict(int $expertId, int $userId, CarbonInterface $start, CarbonInterface $finish, ?int $ignoreReservationId): void
    {
        $conflict = Reservation::query()
            ->where(fn (Builder $query) => $query->where('expert_id', $expertId)->orWhere('user_id', $userId))
            ->where('state_id', '!=', ReservationStates::Cancel->value)
            ->when($ignoreReservationId, fn (Builder $query) => $query->where('id', '!=', $ignoreReservationId))
            ->where('start_time', '<', $finish)
            ->where('finish_time', '>', $start)
            ->exists();

        throw_if($conflict, new \RuntimeException(__('messages.reservation_conflict')));
    }
}
