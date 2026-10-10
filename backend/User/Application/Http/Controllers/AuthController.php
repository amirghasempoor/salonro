<?php

namespace User\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Shared\Traits\ApiResponse;
use Throwable;
use User\Application\Http\Requests\Auth\LoginWithOtpRequest;
use User\Application\Http\Requests\Auth\LoginWithPasswordRequest;
use User\Application\Http\Requests\Auth\RegisterRequest;
use User\Application\Http\Requests\Auth\SendOtpRequest;
use User\Application\Services\AuthService;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        return $this->successResponse($this->authService->register($request));
    }

    /**
     * @throws Throwable
     */
    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $this->authService->sendOtp($request);

        return $this->successResponse();
    }

    /**
     * @throws Throwable
     */
    public function loginWithOtp(LoginWithOtpRequest $request): JsonResponse
    {
        $result = $this->authService->loginWithOtp($request);

        return $result === null ?
            $this->errorResponse(__('messages.incorrect_otp')) :
            $this->successResponse($result);
    }

    public function loginWithPassword(LoginWithPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->loginWithPassword($request);

        return $result === null ?
            $this->errorResponse(__('messages.invalid_credentials')) :
            $this->successResponse($result);
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout(Auth::guard('user')->user());

        return $this->successResponse();
    }
}
