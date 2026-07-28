<?php

namespace App\Expert\Controllers\Manager;

use App\Enums\Roles;
use App\Expert\Requests\Manager\Expert\StoreRequest;
use App\Expert\Requests\Manager\Expert\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ExpertManagementController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, int $hall_id): JsonResponse
    {
        $query = Hall::query()
            ->firstWhere('id', '=', $hall_id)
            ->experts();

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
        );

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $hall = Hall::query()->where('id', '=', $request->query('hall_id'))->firstOrFail();
                $expert = Expert::query()->create([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone_number,
                    'password' => Hash::make($request->password),
                    'province_id' => $hall->province_id,
                    'city_id' => $hall->city_id,
                ]);

                $expert->assignRole(Roles::Expert->value);
                $hall->experts()->attach($expert->id, ['joined_at' => now(),]);
                $expert->services()->sync($request->services);
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expert $expert): JsonResponse
    {
        return $this->successResponse($expert->load('services'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Expert $expert): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $expert) {
                $expert->update([
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone_number,
                ]);
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expert $expert): JsonResponse
    {
        $expert->delete();
        return $this->successResponse();
    }
}
