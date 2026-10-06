<?php

namespace User\Application\Services;

use App\Enums\ReservationStates;
use App\Facades\DataTable\DataTableFacade;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;
use User\Domain\Repositories\HallRepositoryInterface;

class ReservationManagementService
{
    public function __construct(private readonly HallRepositoryInterface $hallRepository) {}

    public function index(Request $request, User $user): array
    {
        $query = Reservation::query()->where('user_id', '=', $user->id);

        return DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'user_name', 'state_name', 'start_time', 'finish_time',
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

    /**
     * Staff at the hall who provide at least one of the requested services —
     * so a customer booking can see who they're able to choose from.
     *
     * @param  list<int>  $serviceIds
     * @return list<array{id: int, first_name: string, last_name: string, avatar: ?string}>
     */
    public function staff(Hall $hall, array $serviceIds): array
    {
        return array_map(
            fn (array $member) => [
                'id' => $member['id'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'avatar' => $member['avatar'],
            ],
            $this->hallRepository->find($hall->id)->staffOffering($serviceIds),
        );
    }

    /**
     * The working hours a staff member has at this hall, so the customer can
     * pick a slot the expert is actually available for.
     *
     * @return Collection<int, WorkingHour>
     */
    public function staffWorkingHours(Hall $hall, Expert $expert): Collection
    {
        return $expert->workingHoursAtHall($hall->id)->get(['day', 'from', 'to']);
    }

    public function staffSchedule(Request $request): Collection
    {
        return Reservation::query()
            ->where('expert_id', '=', $request->expert_id)
            ->where('state_id', '!=', ReservationStates::Cancel->value)
            ->where('start_time', '<', $request->to_date)
            ->where('finish_time', '>', $request->from_date)
            ->orderBy('start_time')
            ->get(['start_time', 'finish_time']);
    }
}
