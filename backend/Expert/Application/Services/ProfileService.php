<?php

namespace Expert\Application\Services;

use App\Enums\Roles;
use App\Facades\File\File;
use Expert\Application\Http\Requests\Profile\ChangePasswordRequest;
use Expert\Application\Http\Requests\Profile\CompleteRequest;
use Expert\Application\Http\Requests\Profile\DefineRoleRequest;
use Expert\Application\Http\Requests\Profile\UpdateRequest;
use Expert\Application\Http\Requests\Profile\UploadPortfolioRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function complete(CompleteRequest $request): void
    {
        Auth::guard('expert')->user()->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'avatar' => $request->avatar ? File::save($request->avatar, '/experts/avatars') : null,
            'bio' => $request->bio,
            'is_verified' => true,
            'password' => Hash::make($request->password),
        ]);
    }

    public function update(UpdateRequest $request): void
    {
        $expert = Auth::guard('expert')->user();

        $data = [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'bio' => $request->bio ?? $expert->bio,
            'is_active' => $request->is_active,
        ];

        if ($request->avatar) {
            if ($expert->avatar) {
                File::delete($expert->avatar, true);
            }

            $data['avatar'] = File::save($request->avatar, '/experts/avatars');
        }

        $expert->update($data);
    }

    public function changePassword(ChangePasswordRequest $request): bool
    {
        $expert = Auth::guard('expert')->user();

        if (! Hash::check($request->current_password, $expert->password)) {
            return false;
        }

        $expert->update([
            'password' => Hash::make($request->new_password),
        ]);

        return true;
    }

    public function uploadPortfolio(UploadPortfolioRequest $request): void
    {
        $expert = Auth::guard('expert')->user();

        foreach ($request->portfolio as $portfolio) {
            $path = File::save($portfolio['image'], "portfolios/{$expert->id}");

            $expert->images()->create(['url' => $path, 'title' => $portfolio['title']]);
        }
    }

    public function defineRole(DefineRoleRequest $request): void
    {
        Auth::guard('expert')->user()->assignRole(Roles::labels($request->role));
    }
}
