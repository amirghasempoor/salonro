<?php

namespace App\User\Controllers;

use App\Facades\DataTable\DataTableFacade;
use App\Http\Controllers\Controller;
use App\Models\Hall;
use App\User\Requests\HomePage\HallsInAreaRequest;
use Illuminate\Http\JsonResponse;

class HomePageController extends Controller
{
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
}
