<?php

namespace Expert\Application\Services;

use App\Facades\DataTable\DataTableFacade;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReservationManagementService
{
    /**
     * A plain expert sees only their own reservations at the hall; the owner
     * and other managers see all of them.
     */
    public function index(Request $request, Hall $hall, Expert $expert): array
    {
        $query = Reservation::query()->with('services')->where('hall_id', '=', $hall->id);

        if ($expert->hasRole('expert')) {
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
        DB::transaction(function () use ($reservation) {
            $reservation->services()->detach();
            $reservation->delete();
        });
    }
}
