<?php

namespace Expert\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\Reservation;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\Reservation\StoreRequest;
use Expert\Application\Http\Requests\Reservation\UpdateRequest;
use Expert\Application\Http\Resources\ReservationDetailsResource;
use Expert\Application\Services\ReservationManagementService;
use Expert\Domain\Actions\Reservation\BookReservationAction;
use Expert\Domain\Actions\Reservation\RescheduleReservationAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ReservationManagementController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ReservationManagementService $reservationManagementService) {}

    public function index(Request $request, Hall $hall): JsonResponse
    {
        return response()->json(
            $this->reservationManagementService->index($request, $hall, Auth::guard('expert')->user())
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StoreRequest $request, Hall $hall, BookReservationAction $action): JsonResponse
    {
        $action->execute($request->toDto($hall, Auth::guard('expert')->id()));

        return $this->successResponse();
    }

    public function show(Reservation $reservation): JsonResponse
    {
        return $this->successResponse(new ReservationDetailsResource($reservation));
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateRequest $request, Hall $hall, Reservation $reservation, RescheduleReservationAction $action): JsonResponse
    {
        $action->execute($reservation->id, $request->toDto());

        return $this->successResponse();
    }

    /**
     * @throws Throwable
     */
    public function destroy(Hall $hall, Reservation $reservation): JsonResponse
    {
        $this->reservationManagementService->destroy($reservation);

        return $this->successResponse();
    }
}
