<?php

use App\Http\Middleware\EnsurePinChanged;
use App\Http\Middleware\EnsureUserIsAdmin;
use App\Http\Middleware\EnsureUserIsDealer;
use App\Http\Middleware\EnsureUserIsFarmer;
use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            // Runs on every authenticated web request.
            // Safely no-ops for guests and for the change-pin route itself.
            EnsurePinChanged::class,
        ]);

        $middleware->alias([
            'admin' => EnsureUserIsAdmin::class,
            'farmer' => EnsureUserIsFarmer::class,
            'dealer' => EnsureUserIsDealer::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
