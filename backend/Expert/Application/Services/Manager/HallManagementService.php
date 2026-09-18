<?php

namespace Expert\Application\Services\Manager;

use App\Facades\DataTable\DataTableFacade;
use App\Models\City;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Province;
use Expert\Application\Http\Requests\Manager\Hall\StoreRequest;
use Expert\Application\Http\Requests\Manager\Hall\UpdateRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class HallManagementService
{
    public function index(Request $request): array
    {
        $query = Expert::query()
            ->find(Auth::guard('expert')->user()->id)
            ->halls()->select(['halls.id', 'owner_name', 'name', 'lat', 'lng', 'address']);

        return DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
        );
    }

    /**
     * @throws Throwable
     */
    public function store(StoreRequest $request): Hall
    {
        return DB::transaction(function () use ($request) {
            $owner = Auth::guard('expert')->user();
            $province = Province::query()->find($request->province_id);
            $city = City::query()->find($request->city_id);

            $hall = Hall::query()->create([
                'name' => $request->name,
                'owner_id' => $owner->id,
                'owner_name' => $owner->last_name,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'telephone' => $request->telephone,
                'province_id' => $province->id,
                'province_name' => $province->name,
                'city_id' => $city->id,
                'city_name' => $city->name,
                'description' => $request->description,
            ]);

            foreach ($request->services as $service) {
                $hall->services()->attach($service['service_id'], [
                    'price' => $service['price'],
                    'duration' => $service['duration'],
                ]);
            }

            $hall->experts()->attach($owner->id);

            return $hall;
        });
    }

    /**
     * @throws Throwable
     */
    public function update(UpdateRequest $request, Hall $hall): void
    {
        DB::transaction(function () use ($request, $hall) {
            $province = Province::query()->find($request->province_id);
            $city = City::query()->find($request->city_id);

            $hall->update([
                'name' => $request->name,
                'lat' => $request->lat,
                'lng' => $request->lng,
                'address' => $request->address,
                'postal_code' => $request->postal_code,
                'telephone' => $request->telephone,
                'province_id' => $province->id,
                'province_name' => $province->name,
                'city_id' => $city->id,
                'city_name' => $city->name,
                'description' => $request->description,
            ]);
        });
    }

    /**
     * @throws Throwable
     */
    public function destroy(Hall $hall): void
    {
        DB::transaction(function () use ($hall) {
            $hall->experts()->detach();
            $hall->services()->detach();
            $hall->delete();
        });
    }
}
