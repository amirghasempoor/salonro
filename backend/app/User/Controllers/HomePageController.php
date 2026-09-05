<?php

namespace App\User\Controllers;

use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\Models\Service;
use App\Traits\ApiResponse;
use App\User\Requests\HomePage\HallsInAreaRequest;
use Illuminate\Http\JsonResponse;

class HomePageController extends Controller
{
    use ApiResponse;

    /**
     * Radius (in kilometres) within which halls are offered to the user.
     */
    private const int SEARCH_RADIUS_KM = 10;

    public function hallsInArea(HallsInAreaRequest $request): JsonResponse
    {
        $query = Hall::query()
            ->nearby((float) $request->query('lat'), (float) $request->query('lng'), self::SEARCH_RADIUS_KM)
            ->orderBy('distance');

        return response()->json(DataTableFacade::run(
            $query,
            $request,
            allowedFilters: ['*'],
            allowedSortings: ['*'],
        ));
    }

    public function hallDetails(Hall $hall): JsonResponse
    {
        return $this->successResponse($hall);
    }

    public function hallServices(Hall $hall): JsonResponse
    {
        $services = $hall->services()->get()->map(function (Service $service) {
            $service->price = $service->pivot->price;
            $service->duration = $service->pivot->duration;

            return $service;
        });

        return $this->successResponse($services);
    }

    public function hallStaff(Hall $hall): JsonResponse
    {
        return $this->successResponse($hall->experts()->get(['first_name', 'last_name', 'avatar', 'experts.id']));
    }
}
