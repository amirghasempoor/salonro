<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        then: function () {
            Route::prefix('expert')
                ->name('expert.')
                ->middleware(SubstituteBindings::class)
                ->group(base_path('routes/expert.php'));

            Route::prefix('user')
                ->name('user.')
                ->middleware(SubstituteBindings::class)
                ->group(base_path('routes/user.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web()->preventRequestForgery(['*']);

        // There is no login page to send guests to; unauthenticated requests get a 401.
        $middleware->redirectGuestsTo(fn () => null);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // This is a JSON-only API: errors must be JSON even when the client sends no
        // Accept header, otherwise unauthenticated requests redirect to a missing
        // `login` route and surface as a 500.
        $exceptions->shouldRenderJsonWhen(fn () => true);
    })->create();
