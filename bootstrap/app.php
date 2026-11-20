<?php

use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\SetSecurityHeaders;
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
        // Rejects requests whose Host header is not listed in APP_TRUSTED_HOSTS
        // (Laravel skips this check in the local environment and in tests).
        $middleware->trustHosts(at: fn () => config('app.trusted_hosts'), subdomains: false);

        $middleware->web(append: [
            SetSecurityHeaders::class,
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
