<?php

namespace App\Expert\Controllers\Manager;

use App\Expert\Requests\Manager\Hall\StoreRequest;
use App\Expert\Requests\Manager\Hall\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HallManagementController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Expert::query()
            ->find(Auth::guard('expert')->user()->id)
            ->halls();

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'name', 'lat', 'lng', 'address',
            ]
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
                Hall::query()->create([
                    'name' => $request->name,
                    'owner_id' => Auth::guard('expert')->user()->id,
                    'lat' => $request->lat,
                    'lng' => $request->lng,
                    'address' => $request->address,
                    'postal_code' => $request->postal_code,
                    'telephone' => $request->telephone,
                    'province_id' => $request->province_id,
                    'city_id' => $request->city_id,
                    'description' => $request->description,
                ]);
            });

            return $this->successResponse();
        }
        catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Hall $hall): JsonResponse
    {
        return $this->successResponse($hall);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Hall $hall): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $hall) {
                $hall->update([
                    'name' => $request->name,
                    'lat' => $request->lat,
                    'lng' => $request->lng,
                    'address' => $request->address,
                    'postal_code' => $request->postal_code,
                    'telephone' => $request->telephone,
                    'province_id' => $request->province_id,
                    'city_id' => $request->city_id,
                ]);
            });

            return $this->successResponse();
        }
        catch (\Throwable $e)
        {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hall $hall): JsonResponse
    {
        try {
            DB::transaction(function () use ($hall) {
                $hall->experts()->detach();
                $hall->delete();
            });
            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
