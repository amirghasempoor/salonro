<?php

namespace Expert\Manager\Staff\Application\Services;

use App\Enums\Roles;
use App\Facades\DataTable\DataTableFacade;
use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\Hall;
use Expert\Manager\Staff\Application\Http\Requests\StoreRequest;
use Expert\Manager\Staff\Application\Http\Requests\UpdateRequest;
use Illuminate\Database\Eloquent\Collection;
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
                ],
                [
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone_number,
                    'province_id' => $hall->province_id,
                    'city_id' => $hall->city_id,
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

    public function update(UpdateRequest $request, Expert $expert): void
    {
        $expert->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
        ]);
    }

    /**
     * @throws Throwable
     */
    public function destroy(Expert $expert): void
    {
        DB::transaction(function () use ($expert) {
            $expert->services()->detach();
            $expert->expertHalls->each(
                fn (ExpertHall $expertHall) => $expertHall->services()->detach()
            );
            $expert->halls()->detach();
            $expert->delete();
        });
    }
}
