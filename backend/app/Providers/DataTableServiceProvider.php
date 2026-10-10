<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Shared\Facades\DataTable\DataTable;

class DataTableServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind('datatable', function () {
            return new DataTable;
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
