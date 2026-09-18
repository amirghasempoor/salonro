<?php

namespace Expert\Application\Services;

use App\Enums\Roles;
use App\Facades\File\File;
use App\Facades\Otp\OtpFacade;
use App\Models\Expert;
use Expert\Application\Http\Requests\LoginWithOtpRequest;
use Expert\Application\Http\Requests\LoginWithPasswordRequest;
use Expert\Application\Http\Requests\RegisterRequest;
use Expert\Application\Http\Requests\SendOtpRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class AuthService
{
    public function register(RegisterRequest $request): array
    {
        $avatar = $request->avatar ?
            File::save($request->avatar, '/experts/avatars')
            : null;

        $expert = Expert::query()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
            'email' => $request->email,
            'province_id' => $request->province_id,
            'city_id' => $request->city_id,
            'avatar' => $avatar,
        ]);

        $expert->assignRole($request->role == 1 ? Roles::Expert->value : Roles::Manager->value);

        return [
            'token' => $expert->createToken('EXPERT_TOKEN', ['*'], now()->addWeek())->plainTextToken,
            'role' => $expert->getRoleNames()->first(),
        ];
    }

    /**
     * @throws Throwable
     */
    public function sendOtp(SendOtpRequest $request): void
    {
        OtpFacade::generate($request->phone_number, __('messages.otp'));
    }

    /**
     * @throws Throwable
     */
    public function loginWithOtp(LoginWithOtpRequest $request): ?array
    {
        if (! OtpFacade::verify($request->phone_number, $request->verification_code)) {
            return null;
        }

        $expert = DB::transaction(function () use ($request) {
            OtpFacade::deactivate($request->phone_number, $request->verification_code);

            return Expert::query()->firstOrCreate([
                'phone_number' => $request->phone_number,
            ]);
        });

        return [
            'token' => $expert->createToken('EXPERT_TOKEN', ['*'], now()->addWeek())->plainTextToken,
            'is_verified' => $expert->is_verified,
            'role' => $expert->getRoleNames()->first(),
        ];
    }

    public function loginWithPassword(LoginWithPasswordRequest $request): ?array
    {
        $expert = Expert::query()->where('phone_number', '=', $request->phone_number)->first();

        if (! $expert || ! Hash::check($request->password, $expert->password)) {
            return null;
        }

        return [
            'token' => $expert->createToken('EXPERT_TOKEN', ['*'], now()->addWeek())->plainTextToken,
            'is_verified' => $expert->is_verified,
            'role' => $expert->getRoleNames()->first(),
        ];
    }

    public function logout(): void
    {
        Auth::guard('expert')->user()->currentAccessToken()->delete();
    }
}
