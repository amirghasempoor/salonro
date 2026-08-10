<?php

namespace App\Expert\Controllers\Manager;

use App\Expert\Requests\Manager\HallService\StoreRequest;
use App\Expert\Requests\Manager\HallService\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\HallService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HallServicesManagementController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Hall $hall): JsonResponse
    {
        $query = HallService::query()->where('hall_id', '=', $hall->id);

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedRelations: ['service'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'service_id', 'description', 'duration', 'price', 'is_active',
            ]
        );

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $hallService = HallService::query()->create([
            'hall_id' => $hall->id,
            'service_id' => $request->service_id,
            'description' => $request->description,
            'duration' => $request->duration,
            'price' => $request->price,
        ]);

        return $this->successResponse([
            'hall_service_id' => $hallService->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Hall $hall, HallService $hallService): JsonResponse
    {
        return $this->successResponse($hallService->load('service'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Hall $hall, HallService $hallService): JsonResponse
    {
        $hallService->update([
            'service_id' => $request->service_id,
            'description' => $request->description,
            'duration' => $request->duration,
            'price' => $request->price,
            'is_active' => $request->is_active,
        ]);

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hall $hall, HallService $hallService): JsonResponse
    {
        $hallService->delete();

        return $this->successResponse();
    }
}
