<?php

namespace Expert\Application\Services\Manager;

use App\Enums\Roles;
use App\Facades\DataTable\DataTableFacade;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Expert\Application\Http\Requests\Manager\Staff\StoreRequest;
use Expert\Application\Http\Requests\Manager\Staff\ToggleActivationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        DB::transaction(function () use ($request, $hall) {
            $expert = Expert::query()->firstOrCreate(
                [
                    'phone_number' => $request->phone_number,
                ]);

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
        DB::transaction(function () use ($hall, $expert) {
            $expertHall = ExpertHall::query()
                ->where('expert_id', '=', $expert->id)
                ->where('hall_id', '=', $hall->id)
                ->first();

            $expertHall?->services()->detach();

            $hall->experts()->detach($expert->id);
        });
    }
}
