<?php

namespace Expert\Application\Services\Manager;

use App\Enums\ReservationStates;
use App\Enums\Roles;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use App\Models\Reservation;
use Expert\Application\Http\Requests\Manager\Staff\StoreRequest;
use Expert\Application\Http\Requests\Manager\Staff\ToggleActivationRequest;
use Expert\Domain\Exceptions\Manager\ExpertAlreadyInHallException;
use Expert\Domain\Exceptions\Manager\SoloHallCannotHaveStaffException;
use Expert\Domain\Exceptions\Manager\UpcomingReservationsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Shared\Facades\DataTable\DataTableFacade;
use Throwable;

class StaffManagementService
{
    public function index(Request $request, Hall $hall): array
    {
        return DataTableFacade::run(
            $hall->experts(),
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: ['experts.id', 'first_name', 'last_name', 'phone_number', 'avatar'],
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StoreRequest $request, Hall $hall): void
    {
        throw_if($hall->is_solo, SoloHallCannotHaveStaffException::forHall());

        DB::transaction(function () use ($request, $hall) {
            $expert = Expert::query()->firstOrCreate(
                [
                    'phone_number' => $request->phone_number,
                ]);

            throw_if(
                $hall->experts()->where('experts.id', $expert->id)->exists(),
                ExpertAlreadyInHallException::forHall(),
            );

            $expert->assignRole(Roles::Expert->value);
            $hall->experts()->attach($expert->id, ['joined_at' => now()]);
            $expertHall = ExpertHall::query()
                ->where('expert_id', '=', $expert->id)
                ->where('hall_id', '=', $hall->id)
                ->first();
            $expertHall->services()->sync($request->services);
        });
    }

    public function toggleActivation(ToggleActivationRequest $request, Hall $hall, Expert $expert): void
    {
        // Switching someone off is blocked while customers are still booked with her.
        throw_if(! $request->boolean('is_active') && $this->hasUpcomingReservations($hall, $expert), UpcomingReservationsException::forExpert());

        $hall->experts()->updateExistingPivot($expert->id, [
            'is_active' => $request->is_active,
        ]);
    }

    /**
     * Remove the expert from this hall only — the expert account itself, its
     * other hall memberships, and its portfolio-level services are untouched.
     *
     * @throws Throwable
     */
    public function destroy(Hall $hall, Expert $expert): void
    {
        throw_if($this->hasUpcomingReservations($hall, $expert), UpcomingReservationsException::forExpert());

        DB::transaction(function () use ($hall, $expert) {
            $expertHall = ExpertHall::query()
                ->where('expert_id', '=', $expert->id)
                ->where('hall_id', '=', $hall->id)
                ->first();

            $expertHall?->services()->detach();

            // Her own hours at this hall go with her membership.
            $expertHall?->workingHours()->delete();

            $hall->experts()->detach($expert->id);
        });
    }

    private function hasUpcomingReservations(Hall $hall, Expert $expert): bool
    {
        return Reservation::query()
            ->where('hall_id', $hall->id)
            ->where('expert_id', $expert->id)
            ->where('state_id', '!=', ReservationStates::Cancel->value)
            ->where('finish_time', '>', now())
            ->exists();
    }
}
