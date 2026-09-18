<?php

namespace Expert\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\LoginWithOtpRequest;
use Expert\Application\Http\Requests\LoginWithPasswordRequest;
use Expert\Application\Http\Requests\RegisterRequest;
use Expert\Application\Http\Requests\SendOtpRequest;
use Expert\Application\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Throwable;

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

        return $result !== null
            ? $this->successResponse($result)
            : $this->errorResponse(__('messages.incorrect_otp'));
    }

    public function loginWithPassword(LoginWithPasswordRequest $request): JsonResponse
    {
        $result = $this->authService->loginWithPassword($request);

        return $result !== null
            ? $this->successResponse($result)
            : $this->errorResponse(__('messages.invalid_credential'));
    }

    public function logout(): JsonResponse
    {
        $this->authService->logout();

        return $this->successResponse();
    }
}
