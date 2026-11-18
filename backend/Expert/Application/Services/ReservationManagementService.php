<?php

namespace Expert\Application\Services;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Shared\Facades\DataTable\DataTableFacade;
use Shared\Services\ReservationCancellation;
use Throwable;

class ReservationManagementService
{
    public function __construct(private readonly ReservationCancellation $cancellation) {}

    /**
     * The hall's owner sees every reservation in it; any other staff member
     * sees only their own.
     */
    public function index(Request $request, Hall $hall, Expert $expert): array
    {
        $query = Reservation::query()->with('services')->where('hall_id', '=', $hall->id);

        if ($hall->owner_id !== $expert->id) {
            $query->where('expert_id', '=', $expert->id);
        }

        return DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'user_name', 'state_name', 'expert_name', 'start_time', 'finish_time',
                'total_price', 'discount_id', 'discount_amount',
            ]
        );
    }

    /**
     * @throws Throwable
     */
    public function destroy(Reservation $reservation): void
    {
        $this->cancellation->execute($reservation);
    }
}
