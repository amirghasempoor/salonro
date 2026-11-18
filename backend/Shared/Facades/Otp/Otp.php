<?php

namespace Shared\Facades\Otp;

use App\Models\Otp as OtpModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Shared\Facades\Sms\Sms;
use Throwable;

class Otp
{
    /**
     * How long a code stays valid. Long enough for a slow SMS to arrive.
     */
    private const int VALID_FOR_MINUTES = 5;

    /**
     * Wrong guesses allowed per phone number inside the window below, from any IP
     * address combined. A 4-digit code has only 9000 values, so the guesses must be
     * counted per number: counting per IP lets an attacker rotate addresses.
     */
    private const int MAX_WRONG_GUESSES = 5;

    private const int WRONG_GUESS_WINDOW_SECONDS = 300;

    /**
     * Send a fresh code. Any code sent earlier to this number stops working, so at
     * most one is ever live.
     *
     * @throws Throwable
     */
    public function generate($phone_number, $message): true
    {
        DB::transaction(function () use ($phone_number, $message) {
            OtpModel::query()
                ->where('phone_number', $phone_number)
                ->where('used', 0)
                ->update(['used' => 1]);

            $otp = OtpModel::query()->create([
                'phone_number' => $phone_number,
                'verification_code' => random_int(1000, 9999),
                'expired_at' => Carbon::now()->addMinutes(self::VALID_FOR_MINUTES),
            ]);

            Sms::send($phone_number, $message.' '.$otp->verification_code);
        });

        return true;
    }

    public function verify($phone_number, $verification_code): bool
    {
        $guessKey = 'otp-guesses:'.$phone_number;

        if (RateLimiter::tooManyAttempts($guessKey, self::MAX_WRONG_GUESSES)) {
            return false;
        }

        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['used', 0],
            ['verification_code', $verification_code],
            ['expired_at', '>', Carbon::now()],
        ])->first();

        if ($otp === null) {
            RateLimiter::hit($guessKey, self::WRONG_GUESS_WINDOW_SECONDS);

            return false;
        }

        RateLimiter::clear($guessKey);

        return true;
    }

    public function deactivate($phone_number, $verification_code): bool
    {
        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['used', 0],
            ['expired_at', '>', Carbon::now()],
            ['verification_code', $verification_code],
        ])->firstOrFail();

        $otp->update([
            'used' => 1,
        ]);

        return true;
    }
}
