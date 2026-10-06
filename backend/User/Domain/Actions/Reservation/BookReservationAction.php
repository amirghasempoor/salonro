<?php

namespace User\Domain\Actions\Reservation;

use Illuminate\Support\Facades\DB;
use Throwable;
use User\Domain\DTOs\Reservation\BookReservationDto;
use User\Domain\Entities\Reservation;
use User\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use User\Domain\Exceptions\Reservation\ReservationConflictException;
use User\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;
use User\Domain\Repositories\HallRepositoryInterface;
use User\Domain\Repositories\Reservation\ReservationRepositoryInterface;

/**
 * Book a customer with an expert at a hall. The booking rules live in the
 * Reservation entity; this loads what it needs, persists the result and
 * grants the best applicable discount.
 */
readonly class BookReservationAction
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
    public function execute(BookReservationDto $dto): Reservation
    {
        return DB::transaction(function () use ($dto) {
            $reservation = Reservation::book(
                hall: $this->halls->find($dto->hallId),
                userId: $dto->userId,
                userName: $dto->userName,
                expertId: $dto->expertId,
                expertName: $this->reservations->expertName($dto->expertId),
                serviceIds: $dto->serviceIds,
                start: $dto->start,
                finish: $dto->finish,
                workingHours: $this->reservations->workingHours($dto->expertId, $dto->hallId),
                busySlots: $this->reservations->busySlotsOn($dto->start, $dto->expertId, $dto->userId),
            );

            $reservation = $this->reservations->save($reservation);

            $this->reservations->applyBestDiscount($reservation);

            return $reservation;
        });
    }
}
