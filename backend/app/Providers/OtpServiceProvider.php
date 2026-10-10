<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Facades\Otp\Otp;

class OtpServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('Otp', function () {
            return new Otp;
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
