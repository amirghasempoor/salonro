<?php

namespace App\User\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\User\Requests\Profile\ChangePasswordRequest;
use App\User\Requests\Profile\EditRequest;
use App\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse;

    public function info(): JsonResponse
    {
        return $this->successResponse(new UserResource(auth()->user()));
    }

    public function edit(EditRequest $request): JsonResponse
    {
        Auth::guard('web')->user()->update([
            'full_name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
        ]);

        return $this->successResponse();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $expert = Auth::guard('web')->user();

        if (! Hash::check($request->current_password, $expert->password))
        {
            return $this->errorResponse(__('messages.incorrect_current_password'));
        }

        $expert->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->successResponse();
    }
}
