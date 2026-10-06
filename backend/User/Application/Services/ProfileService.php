<?php

namespace User\Application\Services;

use App\Facades\File\File;
use App\Models\City;
use App\Models\Province;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use User\Application\Http\Requests\Profile\ChangeAvatarRequest;
use User\Application\Http\Requests\Profile\ChangePasswordRequest;
use User\Application\Http\Requests\Profile\CompleteRequest;
use User\Application\Http\Requests\Profile\EditRequest;

class ProfileService
{
    public function complete(CompleteRequest $request, User $user): void
    {
        $province = Province::query()->find($request->province_id);
        $city = City::query()->find($request->city_id);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'avatar' => $request->avatar ? File::save($request->avatar, '/users/avatars') : null,
            'email' => $request->email,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'kyc_status' => 1,
            'password' => Hash::make($request->password),
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_name' => $province->name,
            'city_name' => $city->name,
        ]);
    }

    public function edit(EditRequest $request, User $user): void
    {
        $province = Province::query()->find($request->province_id);
        $city = City::query()->find($request->city_id);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'gender' => $request->gender,
            'birth_date' => $request->birth_date,
            'province_id' => $province->id,
            'city_id' => $city->id,
            'province_name' => $province->name,
            'city_name' => $city->name,
            'kyc_status' => 1,
        ]);
    }

    /**
     * @return bool false when the current password is incorrect
     */
    public function changePassword(ChangePasswordRequest $request, User $user): bool
    {
        if (! Hash::check($request->current_password, $user->password)) {
            return false;
        }

        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        return true;
    }

    public function changeAvatar(ChangeAvatarRequest $request, User $user): void
    {
        if ($user->avatar) {
            File::delete($user->avatar, true);
        }

        $user->update([
            'avatar' => File::save($request->avatar, '/users/avatars'),
        ]);
    }
}
