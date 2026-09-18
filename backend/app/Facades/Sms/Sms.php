<?php

namespace App\Facades\Sms;

use Illuminate\Support\Facades\Facade;

class Sms extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return SmsClass::class;
    }
}
