<?php

namespace App\Expert\Controllers;

use App\Enums\Roles;
use App\Expert\Requests\Auth\LoginWithOtpRequest;
use App\Expert\Requests\Auth\LoginWithPasswordRequest;
use App\Expert\Requests\Auth\RegisterRequest;
use App\Expert\Requests\Auth\SendOtpRequest;
use App\Expert\Resources\ExpertResource;
use App\Facades\File\File;
use App\Facades\Otp\OtpFacade;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use App\Models\Expert;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $avatar = $request->avatar ?
            File::save($request->avatar, '/experts/avatars')
            : null;

        $expert = Expert::query()->create([
            'full_name' => $request->first_name . ' ' . $request->last_name,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'avatar' => $avatar,
        ]);

        $expert->assignRole($request->role == 1 ? Roles::Expert->value : Roles::Manager->value);

        return $this->successResponse([
            'token' => $expert->createToken("EXPERT_TOKEN", ['*'], now()->addWeek())->plainTextToken,
            'role' => $expert->getRoleNames()->first(),
        ]);
    }

    /**
     * @throws Exception
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        OtpFacade::generate($request->phone_number, __('messages.otp'));
        return $this->successResponse();
    }

    /**
     * @throws Throwable
     */
    public function loginWithOtp(LoginWithOtpRequest $request): JsonResponse
    {
        if (OtpFacade::verify($request->phone_number, $request->verification_code))
        {
            $expert = DB::transaction(function () use ($request) {
                $expert = Expert::query()->firstOrCreate([
                    'phone_number' => $request->phone_number,
                    ]);

                OtpFacade::deactivate($request->phone_number, $request->verification_code);

                return $expert;
            });

            return $this->successResponse([
                'token' => $expert->createToken("EXPERT_TOKEN", ['*'], now()->addWeek())->plainTextToken,
                'is_verified' => $expert->is_verified,
                'role' => $expert->getRoleNames()->first(),
            ]);
        }

        return $this->errorResponse(__('messages.incorrect_otp'));
    }

    public function loginWithPassword(LoginWithPasswordRequest $request): JsonResponse
    {
        $expert = Expert::query()->where('phone_number', '=', $request->phone_number)->first();

        if (!Hash::check($request->password, $expert->password))
        {
            return $this->errorResponse(__('messages.invalid_credential'));
        }

        $token = $expert->createToken("EXPERT_TOKEN", ['*'], now()->addWeek())->plainTextToken;

        return $this->successResponse([
            'token' => $token,
            'is_verified' => $expert->is_verified,
            'role' => $expert->getRoleNames()->first(),
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('expert')->user()->currentAccessToken()->delete();

        return $this->successResponse();
    }
}
