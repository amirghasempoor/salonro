<?php

namespace User\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Hall;
use Illuminate\Http\JsonResponse;
use Shared\Traits\ApiResponse;
use User\Application\Http\Requests\HomePage\HallsInAreaRequest;
use User\Application\Services\HomePageService;

class HomePageController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly HomePageService $homePageService) {}

    public function hallsInArea(HallsInAreaRequest $request): JsonResponse
    {
        return response()->json($this->homePageService->hallsInArea($request));
    }

    public function hallDetails(Hall $hall): JsonResponse
    {
        return $this->successResponse($hall);
    }

    public function hallServices(Hall $hall): JsonResponse
    {
        return $this->successResponse($this->homePageService->hallServices($hall));
    }
}
