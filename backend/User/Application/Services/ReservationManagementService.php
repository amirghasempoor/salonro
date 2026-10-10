<?php

namespace User\Application\Services;

use App\Enums\ReservationStates;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Shared\Facades\DataTable\DataTableFacade;
use Throwable;
use User\Domain\Entities\StaffAvailability;
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
            allowedRelations: ['services:id,sub_cat_name'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'hall_name', 'state_name', 'start_time', 'finish_time',
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
     * so a customer booking can see who they're able to choose from, and
     * which of the requested services each of them can actually perform.
     *
     * @param  list<int>  $serviceIds
     * @return list<array{id: int, first_name: string, last_name: string, avatar: ?string, services: list<string>}>
     */
    public function staff(Hall $hall, array $serviceIds): array
    {
        $hallEntity = $this->hallRepository->find($hall->id);
        $serviceNames = Service::query()->whereIn('id', $serviceIds)->pluck('sub_cat_name', 'id');

        return array_map(
            fn (array $member) => [
                'id' => $member['id'],
                'first_name' => $member['first_name'],
                'last_name' => $member['last_name'],
                'avatar' => $member['avatar'],
                'services' => array_values(array_map(
                    fn (int $serviceId) => $serviceNames[$serviceId],
                    array_intersect($member['service_ids'], $serviceIds),
                )),
            ],
            $hallEntity->staffOffering($serviceIds),
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

    /**
     * The staff member's free slots in the requested period, at this hall:
     * their recurring working hours there, minus any slot they're already
     * booked for — at this hall or any other, since a double-booked expert
     * isn't available anywhere.
     *
     * @return list<array{start_time: string, finish_time: string}>
     */
    public function staffSchedule(Request $request): array
    {
        $expert = Expert::query()->findOrFail($request->expert_id);
        $from = Carbon::parse($request->from_date);
        $to = Carbon::parse($request->to_date);

        $workingHours = $expert->workingHoursAtHall($request->hall_id)
            ->get(['day', 'from', 'to'])
            ->map(fn (WorkingHour $window) => [
                'day' => $window->day,
                'from' => $window->from,
                'to' => $window->to,
            ])
            ->all();

        $busySlots = Reservation::query()
            ->where('expert_id', '=', $expert->id)
            ->where('state_id', '!=', ReservationStates::Cancel->value)
            ->where('start_time', '<', $to)
            ->where('finish_time', '>', $from)
            ->get(['start_time', 'finish_time'])
            ->map(fn (Reservation $reservation) => [
                'start' => $reservation->start_time,
                'finish' => $reservation->finish_time,
            ])
            ->all();

        $availability = new StaffAvailability($workingHours);

        return array_map(
            fn (array $slot) => [
                'start_time' => $slot['start']->format('Y-m-d H:i:s'),
                'finish_time' => $slot['finish']->format('Y-m-d H:i:s'),
            ],
            $availability->freeSlots($from, $to, $busySlots),
        );
    }
}
