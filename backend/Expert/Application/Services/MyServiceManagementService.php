<?php

namespace Expert\Application\Services;

use App\Models\Expert;
use App\Models\ExpertHall;
use App\Models\ExpertService;
use Expert\Application\Http\Requests\MyService\UpdateRequest;
use Illuminate\Database\Eloquent\Collection;

class MyServiceManagementService
{
    /**
     * @return Collection<int, ExpertService>
     */
    public function index(Expert $expert, int $hallId): Collection
    {
        return ExpertService::query()
            ->where('serviceable_type', '=', ExpertHall::class)
            ->whereIn('serviceable_id', ExpertHall::query()
                ->where('expert_id', '=', $expert->id)
                ->where('hall_id', '=', $hallId)
                ->select('id'))
            ->with('service')
            ->get();
    }

    public function update(UpdateRequest $request, ExpertService $expertService): void
    {
        $expertService->update([
            'price' => $request->price,
            'duration' => $request->duration,
        ]);
    }

    public function destroy(ExpertService $expertService): void
    {
        $expertService->delete();
    }
}
