<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Facades\Sms\SmsClass;

class SmsServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('Sms', function () {
            return new SmsClass;
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
