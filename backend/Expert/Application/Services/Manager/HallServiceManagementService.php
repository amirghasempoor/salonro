<?php

namespace Expert\Application\Services\Manager;

use App\Facades\DataTable\DataTableFacade;
use App\Models\Hall;
use App\Models\HallService;
use Expert\Application\Http\Requests\Manager\HallService\StoreRequest;
use Expert\Application\Http\Requests\Manager\HallService\UpdateRequest;
use Illuminate\Http\Request;

class HallServiceManagementService
{
    public function index(Request $request, Hall $hall): array
    {
        return DataTableFacade::run(
            HallService::query()->where('hall_id', '=', $hall->id),
            $request,
            allowedFilters: ['*'],
            allowedRelations: ['service'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'service_id', 'description', 'duration', 'price', 'is_active',
            ]
        );
    }

    public function store(StoreRequest $request, Hall $hall): HallService
    {
        return HallService::query()->create([
            'hall_id' => $hall->id,
            'service_id' => $request->service_id,
            'description' => $request->description,
            'duration' => $request->duration,
            'price' => $request->price,
        ]);
    }

    public function update(UpdateRequest $request, HallService $hallService): void
    {
        $hallService->update([
            'service_id' => $request->service_id,
            'description' => $request->description,
            'duration' => $request->duration,
            'price' => $request->price,
            'is_active' => $request->is_active,
        ]);
    }

    public function destroy(HallService $hallService): void
    {
        $hallService->delete();
    }
}
