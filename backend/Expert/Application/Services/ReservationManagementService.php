<?php

namespace Expert\Application\Services;

use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shared\Facades\DataTable\DataTableFacade;
use Throwable;

class ReservationManagementService
{
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
        DB::transaction(function () use ($reservation) {
            $reservation->services()->detach();
            $reservation->delete();
        });
    }
}
