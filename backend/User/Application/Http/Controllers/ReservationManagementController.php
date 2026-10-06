<?php

namespace User\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;
use User\Application\Http\Requests\Reservation\HallStaffRequest;
use User\Application\Http\Requests\Reservation\StaffScheduleRequest;
use User\Application\Http\Requests\Reservation\StoreRequest;
use User\Application\Http\Requests\Reservation\UpdateRequest;
use User\Application\Services\ReservationManagementService;
use User\Domain\Actions\Reservation\BookReservationAction;
use User\Domain\Actions\Reservation\RescheduleReservationAction;

class ReservationManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReservationManagementService $reservationManagementService) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json(
            $this->reservationManagementService->index($request, Auth::guard('user')->user())
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StoreRequest $request, BookReservationAction $action): JsonResponse
    {
        $action->execute($request->toDto(Auth::guard('user')->user()));

        return $this->successResponse();
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return $this->successResponse($reservation);
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateRequest $request, Reservation $reservation, RescheduleReservationAction $action): JsonResponse
    {
        $action->execute($reservation->id, $request->toDto());

        return $this->successResponse();
    }

    /**
     * @throws Throwable
     */
    public function destroy(Reservation $reservation): JsonResponse
    {
        $this->reservationManagementService->destroy($reservation);

        return $this->successResponse();
    }

    /**
     * Staff in the hall who can be booked for the requested services.
     */
    public function staff(HallStaffRequest $request, Hall $hall): JsonResponse
    {
        return $this->successResponse($this->reservationManagementService->staff($hall, $request->service_ids));
    }

    /**
     * The chosen staff member's working hours at this hall.
     */
    public function staffWorkingHours(Hall $hall, Expert $expert): JsonResponse
    {
        return $this->successResponse($this->reservationManagementService->staffWorkingHours($hall, $expert));
    }

    /**
     * The staff member's already-booked slots in the given period, so the
     * front end can mark them as unavailable.
     */
    public function staffSchedule(StaffScheduleRequest $request): JsonResponse
    {
        return $this->successResponse($this->reservationManagementService->staffSchedule($request));
    }
}
