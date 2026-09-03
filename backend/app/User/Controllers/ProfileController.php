<?php

namespace App\User\Controllers;

use App\Facades\File\File;
use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Province;
use App\Traits\ApiResponse;
use App\User\Requests\Profile\ChangeAvatarRequest;
use App\User\Requests\Profile\ChangePasswordRequest;
use App\User\Requests\Profile\CompleteRequest;
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
        return $this->successResponse(new UserResource(Auth::guard('user')->user()));
    }

    public function complete(CompleteRequest $request): JsonResponse
    {
        $province = Province::query()->find($request->province_id);
        $city = City::query()->find($request->city_id);

        Auth::guard('user')->user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'avatar' => $request->avatar ? File::save($request->avatar, '/users/avatars') : null,
            'email' => $request->email,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'kyc_status' => 1,
            'password' => Hash::make($request->password),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_name' => $province->name,
            'city_name' => $city->name,
        ]);

        return $this->successResponse();
    }

    public function edit(EditRequest $request): JsonResponse
    {
        $province = Province::query()->find($request->province_id);
        $city = City::query()->find($request->city_id);

        Auth::guard('user')->user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_name' => $province->name,
            'city_name' => $city->name,
            'kyc_status' => 1,
        ]);

        return $this->successResponse();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $user = Auth::guard('user')->user();

        if (! Hash::check($request->current_password, $user->password)) {
            return $this->errorResponse(__('messages.incorrect_current_password'));
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->successResponse();
    }

    public function changeAvatar(ChangeAvatarRequest $request): JsonResponse
    {
        $user = Auth::guard('user')->user();

        if ($user->avatar) {
            File::delete($user->avatar, true);
        }

        $user->update([
            'avatar' => File::save($request->avatar, '/users/avatars'),
        ]);

        return $this->successResponse();
    }
}
