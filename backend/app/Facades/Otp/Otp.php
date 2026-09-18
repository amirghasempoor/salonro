<?php

namespace App\Facades\Otp;

use App\Facades\Sms\Sms;
use App\Models\Otp as OtpModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Throwable;

class Otp
{
    /**
     * @throws Throwable
     */
    public function generate($phone_number, $message): true
    {
        DB::transaction(function () use ($phone_number, $message) {
            $otp = OtpModel::query()->where([
                ['phone_number', $phone_number],
                ['created_at', '>', Carbon::now()->tomorrow()->toDateTimeString()]])
                ->orderBy('created_at', 'desc')
                ->first();

            if ($otp) {
                Sms::send($phone_number, $message.' '.$otp->verification_code);

                return true;
            }

            $otp = OtpModel::query()->create([
                'phone_number' => $phone_number,
                'verification_code' => rand(1000, 9999),
                'expired_at' => Carbon::now()->addMinute(2),
            ]);

            Sms::send($phone_number, $message.' '.$otp->verification_code);

        });

        return true;
    }

    public function verify($phone_number, $verification_code): bool
    {
        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['used', 0],
            ['verification_code', $verification_code],
        ])->first();

        return (bool) $otp;
    }

    public function deactivate($phone_number, $verification_code): bool
    {
        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['used', 0],
            ['created_at', '>', Carbon::now()->subDay()], // check 1 day expired time
            ['verification_code', $verification_code],
        ])->firstOrFail();

        $otp->update([
            'used' => 1,
        ]);

        return true;
    }
}
