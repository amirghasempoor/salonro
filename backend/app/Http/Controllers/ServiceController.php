<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    use ApiResponse;

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
                'icon' => $first->icon,
                'templates' => $group->map(function ($row) {
                    return [
                        'id' => $row->id,
                        'sub_cat_id' => $row->sub_cat_id,
                        'name' => $row->sub_cat_name,
                    ];
                })->values(),
            ];
        })->values();

        return response()->json($categories);
    }
}
