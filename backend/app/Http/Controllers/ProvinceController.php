<?php

namespace App\Http\Controllers;

use App\Models\Province;
use App\Traits\ApiResponse;

class ProvinceController extends Controller
{
    use ApiResponse;

    public function list()
    {
        return $this->successResponse(Province::all(['id', 'name', 'center_lat', 'center_lng']));
    }
}
