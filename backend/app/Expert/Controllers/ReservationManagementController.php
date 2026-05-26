<?php

namespace App\Expert\Controllers;

use App\Enums\ReservationStates;
use App\Expert\Requests\Reservation\IndexRequest;
use App\Expert\Requests\Reservation\StoreRequest;
use App\Expert\Requests\Reservation\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Hall;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ReservationManagementController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(IndexRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        $query = Reservation::query()
            ->where('expert_id', '=', $expert->id)
            ->where('hall_id', '=', $request->hall_id);

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                $expert = Auth::guard('expert')->user();

                $user = User::query()->firstOrCreate(
                    [
                        'phone_number' => $request->phone_number
                    ],
                    [
                        'first_name' => $request->first_name,
                        'last_name' => $request->last_name
                    ]
                );

                $reservation = Reservation::query()->create([
                    'user_id' => $user->id,
                    'user_name' => $user->first_name . ' ' . $user->last_name,
                    'expert_id' => $expert->id,
                    'expert_name' => $expert->last_name,
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

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation): JsonResponse
    {
        return $this->successResponse($reservation);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Reservation $reservation): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $reservation) {
                $reservation->update([
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
        catch (\Throwable $e)
        {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
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
