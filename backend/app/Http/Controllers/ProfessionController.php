<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use App\Traits\ApiResponse;

class ProfessionController extends Controller
{
    use ApiResponse;

    public function list()
    {
        return $this->successResponse(Profession::query()->where('is_active', true)->get(['id', 'name']));
    }
}
