<?php

namespace App\Expert\Controllers\Manager;

use App\Enums\Roles;
use App\Expert\Requests\Expert\StoreRequest;
use App\Expert\Requests\Expert\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Facades\File\File;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ExpertManagementController extends Controller
{
    use ApiResponse;

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Hall::query()
            ->firstWhere('id', '=', $request->hall_id)
            ->experts()
            ->get(['id', 'full_name']);

        $data = DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
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
                $avatar = $request->avatar ?
                    File::save($request->avatar, '/experts/avatars')
                    : null;

                $expert = Expert::query()->create([
                    'full_name' => $request->first_name . ' ' . $request->last_name,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone_number,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'province_id' => $request->province_id,
                    'city_id' => $request->city_id,
                    'avatar' => $avatar,
                ]);

                $expert->assignRole(Roles::Expert->value);
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Expert $expert): JsonResponse
    {
        return $this->successResponse($expert);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Expert $expert): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $expert) {
                $expert = Expert::query()->create([
                    'full_name' => $request->first_name . ' ' . $request->last_name,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'phone_number' => $request->phone_number,
                    'password' => Hash::make($request->password),
                    'email' => $request->email,
                    'province_id' => $request->province_id,
                    'city_id' => $request->city_id,
                ]);
            });

            return $this->successResponse();
        } catch (\Throwable $e) {
            return $this->errorResponse($e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Expert $expert): JsonResponse
    {
        $expert->delete();
        return $this->successResponse();
    }
}
