<?php

namespace App\Expert\Controllers;

use App\Expert\Requests\Profile\ChangeAvatarRequest;
use App\Expert\Requests\Profile\ChangePasswordRequest;
use App\Expert\Requests\Profile\DefineWorkingHourRequest;
use App\Expert\Requests\Profile\EditRequest;
use App\Expert\Requests\Profile\UploadPortfolioRequest;
use App\Expert\Resources\ExpertResource;
use App\Facades\File\File;
use App\Http\Controllers\Controller;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    use ApiResponse;

    public function info(): JsonResponse
    {
        return $this->successResponse(new ExpertResource(Auth::guard('expert')->user()));
    }

    public function update(EditRequest $request): JsonResponse
    {
        Auth::guard('expert')->user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'avatar' => $request->avatar,
            'birth_date' => $request->birth_date,
            'bio' => $request->bio,
            'is_verified' => true,
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        if (! Hash::check($request->current_password, $expert->password))
        {
            return $this->errorResponse(__('messages.incorrect_current_password'));
        }

        $expert->update([
            'password' => Hash::make($request->new_password)
        ]);

        return $this->successResponse();
    }

    public function changeAvatar(ChangeAvatarRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        if ($expert->avatar)
        {
            File::delete($expert->avatar);
        }

        $expert->update([
            'avatar' => File::save($request->avatar, '/avatars')
        ]);

        return $this->successResponse();
    }

    public function uploadPortfolio(UploadPortfolioRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        foreach ($request->portfolio as $portfolio)
        {
            $path = File::save($portfolio, "portfolios/{$expert->id}");

            $expert->images()->create(['path' => $path]);
        }
        return $this->successResponse();
    }

    public function defineWorkingHour(DefineWorkingHourRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        foreach ($request->workingHours as $workingHour)
        {
            $expert->workingHours()->create([
                'day' => $workingHour['day'],
                'from' => $workingHour['from'],
                'to' => $workingHour['to'],
            ]);
        }
        return $this->successResponse();
    }
}
