<?php

namespace Expert\Domain\Actions\Reservation;

use Expert\Domain\DTOs\Reservation\RescheduleReservationDto;
use Expert\Domain\Entities\Reservation;
use Expert\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use Expert\Domain\Exceptions\Reservation\ReservationConflictException;
use Expert\Domain\Exceptions\Reservation\ServiceNotPricedByExpertException;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;
use Expert\Domain\Repositories\Reservation\ReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Move an existing reservation to a new slot and re-price its services. The
 * rules live in the Reservation entity; this loads what it needs, persists the
 * result and recalculates the discount the reservation already had.
 */
readonly class RescheduleReservationAction
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private ExpertHallRepositoryInterface $expertHalls,
    ) {}

    /**
     * @throws OutsideWorkingHoursException when the slot does not fit the expert's working hours
     * @throws ReservationConflictException when the expert or the customer is already booked in that slot
     * @throws ServiceNotPricedByExpertException when the expert has not priced a requested service
     * @throws Throwable
     */
    public function execute(int $reservationId, RescheduleReservationDto $dto): Reservation
    {
        return DB::transaction(function () use ($reservationId, $dto) {
            $reservation = $this->reservations->find($reservationId);

            $rescheduled = $reservation->reschedule(
                hall: $this->reservations->findHall($reservation->hallId, $reservation->expertId),
                serviceIds: $dto->serviceIds,
                start: $dto->start,
                finish: $dto->finish,
                workingHours: $this->expertHalls->workingHours($reservation->expertId, $reservation->hallId),
                busySlots: $this->reservations->busySlotsOn($dto->start, $reservation->expertId, $reservation->userId),
            );

            $rescheduled = $this->reservations->save($rescheduled);

            $this->reservations->recomputeDiscount($rescheduled);

            return $rescheduled;
        });
    }
}
