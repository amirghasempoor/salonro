<?php

namespace App\Facades\Otp;

use Illuminate\Support\Facades\Facade;

class OtpFacade extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return Otp::class;
    }
}
