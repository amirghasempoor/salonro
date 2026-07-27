<?php

namespace App\Http\Controllers;

use App\Models\City;
use App\Traits\ApiResponse;
use Illuminate\Http\Request;

class CityController extends Controller
{
    use ApiResponse;

    public function list(int $province_id)
    {
        $cities = City::query()->where('province_id', '=', $province_id)->get(['id', 'province_id', 'name', 'center_lat', 'center_lng']);
        return $this->successResponse($cities);
    }
}
