<?php

namespace Expert\Domain\Actions\Reservation;

use Expert\Domain\DTOs\Reservation\BookReservationDto;
use Expert\Domain\Entities\Reservation;
use Expert\Domain\Exceptions\Reservation\OutsideWorkingHoursException;
use Expert\Domain\Exceptions\Reservation\ReservationConflictException;
use Expert\Domain\Exceptions\Reservation\ServiceNotOfferedByHallException;
use Expert\Domain\Repositories\ExpertHallRepositoryInterface;
use Expert\Domain\Repositories\Reservation\ReservationRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Book a customer with an expert at a hall. The booking rules live in the
 * Reservation entity; this loads what it needs, persists the result and grants
 * the best applicable discount.
 */
readonly class BookReservationAction
{
    public function __construct(
        private ReservationRepositoryInterface $reservations,
        private ExpertHallRepositoryInterface $expertHalls,
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
            $customer = $this->reservations->findOrCreateCustomer($dto->phoneNumber, $dto->firstName, $dto->lastName);

            $reservation = Reservation::book(
                hall: $this->reservations->findHall($dto->hallId),
                userId: $customer['id'],
                userName: $customer['name'],
                expertId: $dto->expertId,
                expertName: $this->reservations->expertName($dto->expertId),
                serviceIds: $dto->serviceIds,
                start: $dto->start,
                finish: $dto->finish,
                workingHours: $this->expertHalls->workingHours($dto->expertId, $dto->hallId),
                busySlots: $this->reservations->busySlotsOn($dto->start, $dto->expertId, $customer['id']),
            );

            $reservation = $this->reservations->save($reservation);

            $this->reservations->applyBestDiscount($reservation);

            return $reservation;
        });
    }
}
