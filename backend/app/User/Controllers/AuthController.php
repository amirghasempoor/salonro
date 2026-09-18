<?php

namespace App\User\Controllers;

use App\Facades\Otp\OtpFacade;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\User\Requests\Auth\LoginWithOtpRequest;
use App\User\Requests\Auth\LoginWithPasswordRequest;
use App\User\Requests\Auth\RegisterRequest;
use App\User\Requests\Auth\SendOtpRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthController extends Controller
{
    use ApiResponse;

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse([
            'token' => $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken,
        ]);
    }

    /**
     * @throws Exception|Throwable
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
        if (OtpFacade::verify($request->phone_number, $request->verification_code)) {
            $user = DB::transaction(function () use ($request) {
                OtpFacade::deactivate($request->phone_number, $request->verification_code);

                return User::query()->firstOrCreate([
                    'phone_number' => $request->phone_number,
                ]);
            });

            return $this->successResponse([
                'token' => $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken,
            ]);
        }

        return $this->errorResponse(__('messages.incorrect_otp'));
    }

    public function loginWithPassword(LoginWithPasswordRequest $request): JsonResponse
    {
        $user = User::query()->where('phone_number', '=', $request->phone_number)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->errorResponse(__('messages.invalid_credentials'));
        }

        return $this->successResponse([
            'token' => $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken,
        ]);
    }

    public function logout(): JsonResponse
    {
        Auth::guard('user')->user()->currentAccessToken()->delete();

        return $this->successResponse();
    }
}
