<?php

namespace App\User\Controllers;

use App\Expert\Requests\Auth\SendOtpRequest;
use App\Facades\Otp\OtpFacade;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponse;
use App\User\Requests\Auth\LoginWithOtpRequest;
use App\User\Requests\Auth\LoginWithPasswordRequest;
use App\User\Requests\Auth\RegisterRequest;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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

        Auth::guard('web')->login($user);

        $request->session()->regenerateToken();

        return $this->successResponse();
    }

    /**
     * @throws Exception
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        OtpFacade::generate($request->phone_number, __('messages.otp'));

        return $this->successResponse();
    }

    public function loginWithOtp(LoginWithOtpRequest $request): JsonResponse
    {
        if (OtpFacade::verify($request->phone_number, $request->verification_code)) {
            OtpFacade::deactivate($request->phone_number, $request->verification_code);

            $user = User::query()->firstWhere('phone_number', $request->phone_number);

            if (! $user) {
                return $this->errorResponse(__('messages.invalid_credential'));
            }

            Auth::guard('web')->login($user);

            $request->session()->regenerateToken();

            return $this->successResponse();
        }

        return $this->errorResponse(__('messages.incorrect_otp'));
    }

    public function loginWithPassword(LoginWithPasswordRequest $request): JsonResponse
    {
        if (Auth::guard('web')->attempt($request->only('phone_number', 'password'))) {
            $request->session()->regenerate();

            return $this->successResponse();
        }

        return $this->errorResponse(__('messages.invalid_credentials'));
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return $this->successResponse();
    }
}
