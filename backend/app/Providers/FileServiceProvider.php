<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Facades\File\File;

class FileServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('file', function () {
            return new File;
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
