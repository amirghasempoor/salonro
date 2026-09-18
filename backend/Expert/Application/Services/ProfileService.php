<?php

namespace Expert\Application\Services;

use App\Enums\Roles;
use App\Facades\File\File;
use Expert\Application\Http\Requests\ChangePasswordRequest;
use Expert\Application\Http\Requests\DefineRoleRequest;
use Expert\Application\Http\Requests\UploadPortfolioRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class ProfileService
{
    public function complete(Request $request): void
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

    public function update(Request $request): void
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
