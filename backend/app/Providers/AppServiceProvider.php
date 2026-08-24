<?php

namespace App\Providers;

use App\Expert\Policies\HallPolicy;
use App\Models\Hall;
use Illuminate\Support\Facades\Gate;
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
        Gate::policy(Hall::class, HallPolicy::class);
    }
}
