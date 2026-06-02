<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::middleware('auth:expert')
                ->prefix('expert')
                ->group(base_path('routes/expert.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web()->preventRequestForgery(['*',]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
