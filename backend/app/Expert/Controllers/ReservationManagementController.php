<?php

namespace App\Expert\Controllers;

use App\Enums\ReservationStates;
use App\Expert\Requests\Reservation\StoreRequest;
use App\Expert\Requests\Reservation\UpdateRequest;
use App\Expert\Resources\ReservationDetailsResource;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\User;
use App\Services\DiscountService;
use App\Traits\ApiResponse;
use App\Traits\ResolvesReservationPricing;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationManagementController extends Controller
{
    use ApiResponse;
    use ResolvesReservationPricing;

    public function __construct(
        private readonly DiscountService $discountService,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, Hall $hall): JsonResponse
    {
        $this->ensureHallAccess($hall);

        $expert = Auth::guard('expert')->user();

        $query = Reservation::query()->with('services')->where('hall_id', '=', $hall->id);

        if ($expert->hasRole('expert')) {
            $query->where('expert_id', '=', $expert->id);
        }

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'user_name', 'state_name', 'expert_name', 'start_time', 'finish_time',
                'total_price', 'discount_id', 'discount_amount',
            ]
        );

        return response()->json($data);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request, Hall $hall): JsonResponse
    {
        $this->ensureHallAccess($hall);

        try {
            DB::transaction(function () use ($request, $hall) {
                $expert = Auth::guard('expert')->user();

                $user = User::query()->firstOrCreate(
                    [
                        'phone_number' => $request->phone_number,
                    ],
                    [
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name,
                    ]
                );

                $priced = $this->priceReservationServices($hall->id, $request->services);

                $reservation = Reservation::query()->create([
                    'user_id' => $user->id,
                    'user_name' => $user->first_name.' '.$user->last_name,
                    'expert_id' => $expert->id,
                    'expert_name' => $expert->full_name,
                    'hall_id' => $hall->id,
                    'hall_name' => $hall->name,
                    'state_id' => ReservationStates::Reserve->value,
                    'state_name' => ReservationStates::Reserve->label(),
                    'start_time' => $request->start_time,
                    'finish_time' => $request->finish_time,
                    'total_price' => $priced['baseTotal'],
                ]);

                $reservation->services()->attach($priced['pivot']);

                $best = $this->discountService->resolveBest(
                    $hall->id,
                    $user->id,
                    Carbon::parse($request->start_time),
                    $priced['baseTotal'],
                );

                if ($best !== null) {
                    $this->discountService->apply($reservation, $best['discount'], $priced['baseTotal']);
                }
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation): JsonResponse
    {
        $this->ensureHallAccess($reservation->hall);

        return $this->successResponse(new ReservationDetailsResource($reservation));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Hall $hall, Reservation $reservation): JsonResponse
    {
        $this->ensureHallAccess($hall);
        abort_unless($reservation->hall_id === $hall->id, 404);

        try {
            DB::transaction(function () use ($request, $hall, $reservation) {
                $priced = $this->priceReservationServices($hall->id, $request->services);

                $reservation->update([
                    'start_time' => $request->start_time,
                    'finish_time' => $request->finish_time,
                ]);

                $reservation->services()->sync($priced['pivot']);

                $this->discountService->recomputeExisting($reservation, $hall->id, $priced['baseTotal']);
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hall $hall, Reservation $reservation): JsonResponse
    {
        $this->ensureHallAccess($hall);
        abort_unless($reservation->hall_id === $hall->id, 404);

        try {
            DB::transaction(function () use ($reservation) {
                $reservation->services()->detach();
                $reservation->delete();
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Ensure the acting expert owns or is assigned to the hall; abort 403 otherwise.
     */
    private function ensureHallAccess(Hall $hall): void
    {
        $expert = Auth::guard('expert')->user();

        $canAccess = $hall->owner_id === $expert->id
            || $hall->experts()->where('experts.id', $expert->id)->exists();

        abort_unless($canAccess, 403);
    }
}
