<?php

namespace App\Expert\Controllers\Manager;

use App\Expert\Requests\Manager\HallService\StoreRequest;
use App\Expert\Requests\Manager\HallService\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HallServicesManagementController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Service::query()->where('hall_id', '=', $request->hall_id);

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'name', 'duration', 'price'
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
                Service::query()->create([
                    'name' => $request->name,
                    'hall_id' => $request->hall_id,
                    'category_id' => $request->category_id,
                    'description' => $request->description,
                    'duration' => $request->duration,
                    'price' => $request->price
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
    public function show(Service $service): JsonResponse
    {
        return $this->successResponse($service);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Service $service): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $service) {
                $service->update([
                    'name' => $request->name,
                    'category_id' => $request->category_id,
                    'description' => $request->description,
                    'duration' => $request->duration,
                    'price' => $request->price,
                    'is_active' => $request->is_active
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
    public function destroy(Service $service): JsonResponse
    {
        try {
            DB::transaction(function () use ($service) {
                $service->delete();
            });
            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }
}
