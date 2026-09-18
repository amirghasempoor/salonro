<?php

namespace App\Expert\Controllers\Manager;

use App\Enums\DiscountType;
use App\Expert\Requests\Manager\Discount\StoreRequest;
use App\Expert\Requests\Manager\Discount\UpdateRequest;
use App\Expert\Resources\DiscountDetailsResource;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Discount;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DiscountManagementController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Hall $hall): JsonResponse
    {
        $this->ensureOwnership($hall);

        $query = Discount::query()->where('hall_id', '=', $hall->id);

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedRelations: ['user'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'type', 'title', 'user_id', 'amount_type', 'amount',
                'starts_at', 'ends_at', 'usage_limit', 'used_count', 'is_active',
            ]
        );

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $this->ensureOwnership($hall);

        $discount = Discount::query()->create([
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
            'created_by' => Auth::guard('expert')->id(),
        ]);

        return $this->successResponse([
            'discount_id' => $discount->id,
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(Hall $hall, Discount $discount): JsonResponse
    {
        $this->ensureOwnership($hall);
        $this->ensureBelongsToHall($discount, $hall);

        return $this->successResponse(new DiscountDetailsResource($discount->load('user')));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Hall $hall, Discount $discount): JsonResponse
    {
        $this->ensureOwnership($hall);
        $this->ensureBelongsToHall($discount, $hall);

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

        return $this->successResponse();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hall $hall, Discount $discount): JsonResponse
    {
        $this->ensureOwnership($hall);
        $this->ensureBelongsToHall($discount, $hall);

        $discount->delete();

        return $this->successResponse();
    }

    private function isManual(Request $request): bool
    {
        return $request->type === DiscountType::Manual->value;
    }

    /**
     * Only the hall's owning manager may manage its discounts.
     */
    private function ensureOwnership(Hall $hall): void
    {
        abort_unless($hall->owner_id === Auth::guard('expert')->user()->id, 403);
    }

    private function ensureBelongsToHall(Discount $discount, Hall $hall): void
    {
        abort_unless($discount->hall_id === $hall->id, 404);
    }
}
