<?php

namespace App\Http\Controllers;

use App\Models\Province;
use Shared\Traits\ApiResponse;

class ProvinceController extends Controller
{
    use ApiResponse;

    public function list()
    {
        return $this->successResponse(Province::all(['id', 'name', 'center_lat', 'center_lng']));
    }
}
