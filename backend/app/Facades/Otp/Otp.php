<?php

namespace App\Facades\Otp;

use Exception;
use App\Facades\Sms\Sms;
use Carbon\Carbon;
use App\Models\Otp as OtpModel;
use SoapFault;
use SoapClient;

class Otp
{
    /**
     * @throws Exception
     */
    public function generate($phone_number, $message): true
    {
        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['created_at', '>', Carbon::now()->tomorrow()->toDateTimeString()]])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($otp) {
            Sms::send($phone_number, $message . " " . $otp->verification_code);
            return true;
        }

        $otp = OtpModel::query()->create([
            'phone_number' => $phone_number,
            'verification_code' => rand(100000, 999999),
            'expired_at' => Carbon::now()->addMinute(2),
        ]);

        Sms::send($phone_number, $message . " " . $otp->verification_code);

        return true;
    }

    public function verify($phone_number, $verification_code): bool
    {
        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['used', 0],
            ['verification_code', $verification_code]
        ])->first();

        return (bool)$otp;
    }


    public function deactivate($phone_number, $verification_code): bool
    {
        $otp = OtpModel::query()->where([
            ['phone_number', $phone_number],
            ['used', 0],
            ['created_at', '>', Carbon::now()->tomorrow()->toDateTimeString()], // check 1 day expired time
            ['verification_code', $verification_code]
        ])->firstOrFail();

        $otp->update([
            'used' => 1
        ]);

        return true;
    }
}
