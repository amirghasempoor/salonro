<?php

namespace Expert\Application\Services\Manager;

use App\Enums\DiscountType;
use App\Facades\DataTable\DataTableFacade;
use App\Models\Discount;
use App\Models\Expert;
use App\Models\Hall;
use Expert\Application\Http\Requests\Manager\Discount\StoreRequest;
use Expert\Application\Http\Requests\Manager\Discount\UpdateRequest;
use Illuminate\Http\Request;

class DiscountManagementService
{
    public function index(Request $request, Hall $hall): array
    {
        return DataTableFacade::run(
            Discount::query()->where('hall_id', '=', $hall->id),
            $request,
            allowedFilters: ['*'],
            allowedRelations: ['user'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'type', 'title', 'user_id', 'amount_type', 'amount',
                'starts_at', 'ends_at', 'usage_limit', 'used_count', 'is_active',
            ]
        );
    }

    public function store(StoreRequest $request, Hall $hall, Expert $creator): Discount
    {
        return Discount::query()->create([
            'hall_id' => $hall->id,
            'type' => $request->type,
            'user_id' => $this->isManual($request) ? $request->user_id : null,
            'title' => $request->title,
            'amount_type' => $request->amount_type,
            'amount' => $request->amount,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'usage_limit' => $request->usage_limit,
            'is_active' => $request->boolean('is_active', true),
            'created_by' => $creator->id,
        ]);
    }

    public function show(Discount $discount): Discount
    {
        return $discount->load('user');
    }

    public function update(UpdateRequest $request, Discount $discount): void
    {
        $discount->update([
            'type' => $request->type,
            'user_id' => $this->isManual($request) ? $request->user_id : null,
            'title' => $request->title,
            'amount_type' => $request->amount_type,
            'amount' => $request->amount,
            'starts_at' => $request->starts_at,
            'ends_at' => $request->ends_at,
            'usage_limit' => $request->usage_limit,
            'is_active' => $request->is_active,
        ]);
    }

    public function destroy(Discount $discount): void
    {
        $discount->delete();
    }

    private function isManual(Request $request): bool
    {
        return $request->type === DiscountType::Manual->value;
    }
}
