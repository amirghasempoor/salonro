<?php

namespace User\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Shared\Traits\ApiResponse;
use User\Application\Http\Requests\Profile\ChangeAvatarRequest;
use User\Application\Http\Requests\Profile\ChangePasswordRequest;
use User\Application\Http\Requests\Profile\CompleteRequest;
use User\Application\Http\Requests\Profile\EditRequest;
use User\Application\Http\Resources\UserResource;
use User\Application\Services\ProfileService;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ProfileService $profileService) {}

    public function info(): JsonResponse
    {
        return $this->successResponse(new UserResource(Auth::guard('user')->user()));
    }

    public function complete(CompleteRequest $request): JsonResponse
    {
        $this->profileService->complete($request, Auth::guard('user')->user());

        return $this->successResponse();
    }

    public function edit(EditRequest $request): JsonResponse
    {
        $this->profileService->edit($request, Auth::guard('user')->user());

        return $this->successResponse();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $changed = $this->profileService->changePassword($request, Auth::guard('user')->user());

        if (! $changed) {
            return $this->errorResponse(__('messages.incorrect_current_password'));
        }

        return $this->successResponse();
    }

    public function changeAvatar(ChangeAvatarRequest $request): JsonResponse
    {
        $this->profileService->changeAvatar($request, Auth::guard('user')->user());

        return $this->successResponse();
    }
}
