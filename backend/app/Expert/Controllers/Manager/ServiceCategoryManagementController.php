<?php

namespace App\Expert\Controllers\Manager;

use App\Expert\Requests\Manager\ServiceCategory\StoreRequest;
use App\Expert\Requests\Manager\ServiceCategory\UpdateRequest;
use App\Facades\DataTable\DataTableFacade;
use App\Facades\File\File;
use App\Http\Controllers\Controller;
use App\Models\Expert;
use App\Models\Hall;
use App\Models\ServiceCategory;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceCategoryManagementController extends Controller
{
    use ApiResponse;
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        $data = DataTableFacade::run(
            ServiceCategory::query(),
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
        $categories = ServiceCategory::query()
            ->orderBy('cat_id')
            ->groupBy('cat_id')
            ->get();

        return $this->successResponse($categories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRequest $request): JsonResponse
    {
        try {
            DB::transaction(function () use ($request) {
                ServiceCategory::query()->create([
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
    public function show(ServiceCategory $category): JsonResponse
    {
        return $this->successResponse($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, ServiceCategory $category): JsonResponse
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
    public function destroy(ServiceCategory $category): JsonResponse
    {
        $category->delete();
        return $this->successResponse();
    }
}
