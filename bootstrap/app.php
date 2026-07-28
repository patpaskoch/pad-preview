<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\EnsureAdmin::class,
        ]);

        // /log is also hit via navigator.sendBeacon() on page unload, which
        // can't attach a CSRF header — it's rate-limited instead (see routes).
        $middleware->validateCsrfTokens(except: ['log']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
