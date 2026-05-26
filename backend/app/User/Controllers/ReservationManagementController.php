<?php

namespace App\User\Controllers;

use App\Enums\ReservationStates;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\Service;
use App\Traits\ApiResponse;
use App\User\Requests\Reservation\StoreRequest;
use App\User\Requests\Reservation\UpdateRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationManagementController extends Controller
{
    use ApiResponse;

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
                'id', 'user_name', 'state_name', 'from_date', 'to_date',
            ]
        );

        return response()->json($data);
    }

    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $user = Auth::guard('web')->user();

                $reservation = Reservation::query()->create([
                    'user_id' => $user->id,
                    'user_name' => $user->first_name . ' ' . $user->last_name,
                    'expert_id' => $request->expert_id,
                    'expert_name' => Expert::query()->find($request->expert_id)->last_name,
                    'hall_id' => $request->hall_id,
                    'hall_name' => Hall::query()->find($request->hall_id)->name,
                    'state_id' => ReservationStates::Reserve->value,
                    'state_name' => ReservationStates::Reserve->label(),
                    'start_time' => $request->start_time,
                    'finish_time' => $request->finish_time,
                    'total_price' => $request->total_price,
                ]);

                foreach ($request->services as $service) {
                    $reservation->services()->attach($service['id'], [
                        'service_name' => $service['name'],
                        'price' => $service['price'],
                        'duration' => $service['duration'],
                    ]);
                }
            });

            return $this->successResponse();
        }
        catch (\Throwable $e) {
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
                $reservation->update([
                    'expert_id' => $request->expert_id,
                    'expert_name' => Expert::query()->find($request->expert_id)->full_name,
                    'hall_id' => $request->hall_id,
                    'hall_name' => Hall::query()->find($request->hall_id)->name,
                    'start_time' => $request->start_time,
                    'finish_time' => $request->finish_time,
                    'total_price' => $request->total_price,
                ]);

                foreach ($request->services as $service) {
                    $reservation->services()->sync($service['id'], [
                        'service_name' => $service['name'],
                        'price' => $service['price'],
                        'duration' => $service['duration'],
                    ]);
                }
            });

            return $this->successResponse();
        }
        catch (\Throwable $e)
        {
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
