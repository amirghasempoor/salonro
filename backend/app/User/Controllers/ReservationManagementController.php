<?php

namespace App\User\Controllers;

use App\Enums\ReservationStates;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Services\DiscountService;
use App\Traits\ApiResponse;
use App\Traits\ResolvesReservationPricing;
use App\User\Requests\Reservation\StoreRequest;
use App\User\Requests\Reservation\UpdateRequest;
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

    public function index(Request $request): JsonResponse
    {
        $query = Reservation::query()
            ->where('user_id', '=', Auth::guard('web')->id());

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'user_name', 'state_name', 'start_time', 'finish_time',
            ]
        );

        return response()->json($data);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $user = Auth::guard('web')->user();

                $priced = $this->priceReservationServices((int) $request->hall_id, $request->services);

                $reservation = Reservation::query()->create([
                    'user_id' => $user->id,
                    'user_name' => $user->first_name.' '.$user->last_name,
                    'expert_id' => $request->expert_id,
                    'expert_name' => Expert::query()->find($request->expert_id)->full_name,
                    'hall_id' => $request->hall_id,
                    'hall_name' => Hall::query()->find($request->hall_id)->name,
                    'state_id' => ReservationStates::Reserve->value,
                    'state_name' => ReservationStates::Reserve->label(),
                    'start_time' => $request->start_time,
                    'finish_time' => $request->finish_time,
                    'total_price' => $priced['baseTotal'],
                ]);

                $reservation->services()->attach($priced['pivot']);

                $best = $this->discountService->resolveBest(
                    (int) $request->hall_id,
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

    public function show(Reservation $reservation): JsonResponse
    {
        return $this->successResponse($reservation);
    }

    public function update(UpdateRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $reservation) {
                $priced = $this->priceReservationServices((int) $request->hall_id, $request->services);

                $reservation->update([
                    'expert_id' => $request->expert_id,
                    'expert_name' => Expert::query()->find($request->expert_id)->full_name,
                    'hall_id' => $request->hall_id,
                    'hall_name' => Hall::query()->find($request->hall_id)->name,
                    'start_time' => $request->start_time,
                    'finish_time' => $request->finish_time,
                ]);

                $reservation->services()->sync($priced['pivot']);

                $this->discountService->recomputeExisting($reservation, (int) $request->hall_id, $priced['baseTotal']);
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    public function destroy(Reservation $reservation): JsonResponse
    {
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
}
