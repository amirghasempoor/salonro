<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    /**
     * Throttle the unauthenticated endpoints that cost money (an SMS per OTP) or
     * can be brute-forced (password and OTP guessing). Each is limited per phone
     * number and per IP, so one attacker can neither hammer a single account nor
     * spread the same abuse across many numbers from one address.
     */
    private function configureRateLimiting(): void
    {
        RateLimiter::for('otp', fn (Request $request) => [
            Limit::perMinutes(10, 3)->by('otp:phone:'.$request->input('phone_number')),
            Limit::perHour(20)->by('otp:ip:'.$request->ip()),
        ]);

        RateLimiter::for('login', fn (Request $request) => [
            Limit::perMinute(5)->by('login:phone:'.$request->input('phone_number').'|'.$request->ip()),
            Limit::perMinute(30)->by('login:ip:'.$request->ip()),
        ]);
    }
}
