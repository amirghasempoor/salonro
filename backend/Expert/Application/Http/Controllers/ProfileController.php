<?php

namespace Expert\Application\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Expert\Application\Http\Requests\ChangePasswordRequest;
use Expert\Application\Http\Requests\CompleteRequest;
use Expert\Application\Http\Requests\DefineRoleRequest;
use Expert\Application\Http\Requests\DefineWorkingHourRequest;
use Expert\Application\Http\Requests\UpdateRequest;
use Expert\Application\Http\Requests\UploadPortfolioRequest;
use Expert\Application\Http\Resources\ExpertProfileResource;
use Expert\Application\Services\ProfileService;
use Expert\Domain\Actions\SetWorkingHoursAction;
use Expert\Domain\Exceptions\ExpertNotAssignedToHallException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Throwable;

class ProfileController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly ProfileService $profileService) {}

    public function info(): JsonResponse
    {
        return $this->successResponse(new ExpertProfileResource(Auth::guard('expert')->user()));
    }

    public function complete(CompleteRequest $request): JsonResponse
    {
        $this->profileService->complete($request);

        return $this->successResponse();
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        $this->profileService->update($request);

        return $this->successResponse();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        return $this->profileService->changePassword($request)
            ? $this->successResponse()
            : $this->errorResponse(__('messages.incorrect_current_password'));
    }

    public function uploadPortfolio(UploadPortfolioRequest $request): JsonResponse
    {
        $this->profileService->uploadPortfolio($request);

        return $this->successResponse();
    }

    /**
     * @throws ExpertNotAssignedToHallException
     * @throws Throwable
     */
    public function defineWorkingHour(DefineWorkingHourRequest $request, int $hall, SetWorkingHoursAction $action): JsonResponse
    {
        $action->execute(Auth::guard('expert')->user(), $hall, $request->toDto());

        return $this->successResponse();
    }

    public function defineRole(DefineRoleRequest $request): JsonResponse
    {
        $this->profileService->defineRole($request);

        return $this->successResponse();
    }
}
