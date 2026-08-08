<?php

namespace App\Expert\Controllers\Manager;

use App\Expert\Requests\Manager\ServiceCategory\StoreRequest;
use App\Expert\Requests\Manager\ServiceCategory\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Facades\File\File;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceManagementController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $data = DataTableFacade::run(
            Service::query(),
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
            allowedSelects: [
                'id', 'cat_id', 'cat_name', 'sub_cat_id', 'sub_cat_name', 'icon'
            ]
        );

        return response()->json($data);
    }

    public function list(): JsonResponse
    {
        $rows = Service::query()->orderBy('cat_id')
            ->orderBy('sub_cat_id')
            ->get();

        $categories = $rows->groupBy('cat_id')->map(function ($group) {
                $first = $group->first();
                return [
                    'cat_id' => $first->cat_id,
                    'title' => $first->cat_name,
                    'icon'  => $first->icon,
                    'templates' => $group->map(function ($row) {
                        return [
                            'id' => $row->id,
                            'sub_cat_id'   => $row->sub_cat_id,
                            'name' => $row->sub_cat_name,
                        ];
                    })->values(),
                ];
            })->values();

        return response()->json($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                Service::query()->create([
                    'cat_id' => $request->cat_id,
                    'cat_name' => $request->cat_name,
                    'sub_cat_id' => $request->sub_cat_id,
                    'sub_cat_name' => $request->sub_cat_name,
                    'icon' => File::save($request->icon, '/categories')
                ]);
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
    public function show(Service $category): JsonResponse
    {
        return $this->successResponse($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, Service $category): JsonResponse
    {
        try {
            DB::transaction(function () use ($request, $category) {
                $category->update([
                    'cat_id' => $request->cat_id,
                    'cat_name' => $request->cat_name,
                    'sub_cat_id' => $request->sub_cat_id,
                    'sub_cat_name' => $request->sub_cat_name,
                    'icon' => File::save($request->icon, '/categories')
                ]);
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
    public function destroy(Service $category): JsonResponse
    {
        $category->delete();
        return $this->successResponse();
    }
}
