<?php

namespace User\Application\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Shared\Facades\Otp\OtpFacade;
use Throwable;
use User\Application\Http\Requests\Auth\LoginWithOtpRequest;
use User\Application\Http\Requests\Auth\LoginWithPasswordRequest;
use User\Application\Http\Requests\Auth\RegisterRequest;
use User\Application\Http\Requests\Auth\SendOtpRequest;

class AuthService
{
    /**
     * @return array{token: string}
     */
    public function register(RegisterRequest $request): array
    {
        $user = User::query()->create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($request->password),
        ]);

        return ['token' => $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken];
    }

    /**
     * @throws Throwable
     */
    public function sendOtp(SendOtpRequest $request): void
    {
        OtpFacade::generate($request->phone_number, __('messages.otp'));
    }

    /**
     * @return array{token: string}|null null when the OTP is invalid
     *
     * @throws Throwable
     */
    public function loginWithOtp(LoginWithOtpRequest $request): ?array
    {
        if (! OtpFacade::verify($request->phone_number, $request->verification_code)) {
            return null;
        }

        $user = DB::transaction(function () use ($request) {
            OtpFacade::deactivate($request->phone_number, $request->verification_code);

            return User::query()->firstOrCreate([
                'phone_number' => $request->phone_number,
            ]);
        });

        return ['token' => $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken];
    }

    /**
     * @return array{token: string}|null null when the credentials are invalid
     */
    public function loginWithPassword(LoginWithPasswordRequest $request): ?array
    {
        $user = User::query()->where('phone_number', '=', $request->phone_number)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return null;
        }

        return ['token' => $user->createToken('USER_TOKEN', ['*'], now()->addWeek())->plainTextToken];
    }

    public function logout(User $user): void
    {
        $user->currentAccessToken()->delete();
    }
}
