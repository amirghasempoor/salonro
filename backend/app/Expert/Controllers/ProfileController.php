<?php

namespace App\Expert\Controllers;

use App\Enums\Roles;
use App\Expert\Requests\Profile\ChangePasswordRequest;
use App\Expert\Requests\Profile\CompleteRequest;
use App\Expert\Requests\Profile\DefineRoleRequest;
use App\Expert\Requests\Profile\DefineWorkingHourRequest;
use App\Expert\Requests\Profile\UpdateRequest;
use App\Expert\Requests\Profile\UploadPortfolioRequest;
use App\Expert\Resources\ExpertProfileResource;
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
        return $this->successResponse(new ExpertProfileResource(Auth::guard('expert')->user()));
    }

    public function complete(CompleteRequest $request): JsonResponse
    {
        Auth::guard('expert')->user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'avatar' => $request->avatar ? File::save($request->avatar, '/experts/avatars') : null,
            'bio' => $request->bio,
            'is_verified' => true,
            'password' => Hash::make($request->password),
        ]);

        return $this->successResponse();
    }

    public function update(UpdateRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        if ($request->avatar) {
            if ($expert->avatar) {
                File::delete($expert->avatar, true);
            }

            $avatar = File::save($request->avatar, '/experts/avatars');
        }

        $expert->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'avatar' => $avatar ?? $expert->avatar,
            'bio' => $request->bio ?? $expert->bio,
            'is_active' => $request->is_active,
        ]);

        return $this->successResponse();
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        if (! Hash::check($request->current_password, $expert->password)) {
            return $this->errorResponse(__('messages.incorrect_current_password'));
        }

        $expert->update([
            'password' => Hash::make($request->new_password),
        ]);

        return $this->successResponse();
    }

    public function uploadPortfolio(UploadPortfolioRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        foreach ($request->portfolio as $portfolio) {
            $path = File::save($portfolio['image'], "portfolios/{$expert->id}");

            $expert->images()->create(['url' => $path, 'title' => $portfolio['title']]);
        }

        return $this->successResponse();
    }

    public function defineWorkingHour(DefineWorkingHourRequest $request, int $hall): JsonResponse
    {
        $expert = Auth::guard('expert')->user();

        $expertHall = $expert->expertHalls()
            ->where('hall_id', '=', $hall)
            ->where('is_active', '=', true)
            ->firstOrFail();

        if (! $expertHall) {
            return $this->errorResponse(__('messages.expert_not_in_hall'));
        }

        foreach ($request->workingHours as $workingHour) {
            $expertHall->workingHours()->create([
                'day' => $workingHour['day'],
                'from' => $workingHour['from'],
                'to' => $workingHour['to'],
            ]);
        }

        return $this->successResponse();
    }

    public function defineRole(DefineRoleRequest $request): JsonResponse
    {
        $expert = Auth::guard('expert')->user();
        $expert->assignRole(Roles::labels($request->role));

        return $this->successResponse();
    }
}
