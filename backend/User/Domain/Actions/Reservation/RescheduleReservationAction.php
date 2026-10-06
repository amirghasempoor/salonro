<?php

namespace User\Domain\Actions\Reservation;

use Illuminate\Support\Facades\DB;
use Throwable;
use User\Domain\DTOs\Reservation\RescheduleReservationDto;
use User\Domain\Entities\Reservation;
use User\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use User\Domain\Exceptions\Reservation\ReservationConflictException;
use User\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;
use User\Domain\Repositories\HallRepositoryInterface;
use User\Domain\Repositories\Reservation\ReservationRepositoryInterface;

/**
 * Move an existing reservation to a new slot — possibly with a different
 * expert or hall — and re-price its services. The rules live in the
 * Reservation entity; this loads what it needs, persists the result and
 * recalculates the discount the reservation already had.
 */
readonly class RescheduleReservationAction
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private HallRepositoryInterface $halls,
    ) {}

    /**
     * @throws OutsideWorkingHoursException when the slot does not fit the expert's working hours
     * @throws ReservationConflictException when the expert or the customer is already booked in that slot
     * @throws ServiceNotOfferedByHallException when a requested service is not offered by the hall
     * @throws Throwable
     */
    public function execute(int $reservationId, RescheduleReservationDto $dto): Reservation
    {
        return DB::transaction(function () use ($reservationId, $dto) {
            $reservation = $this->reservations->find($reservationId);

            $rescheduled = $reservation->reschedule(
                hall: $this->halls->find($dto->hallId),
                expertId: $dto->expertId,
                expertName: $this->reservations->expertName($dto->expertId),
                serviceIds: $dto->serviceIds,
                start: $dto->start,
                finish: $dto->finish,
                workingHours: $this->reservations->workingHours($dto->expertId, $dto->hallId),
                busySlots: $this->reservations->busySlotsOn($dto->start, $dto->expertId, $reservation->userId),
            );

            $rescheduled = $this->reservations->save($rescheduled);

            $this->reservations->recomputeDiscount($rescheduled);

            return $rescheduled;
        });
    }
}
