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
        // Symfony treats each entry as a regular expression, so host names are
        // escaped and anchored: "shop.test" must not also match "shop.test.evil.net".
        $middleware->trustHosts(
            at: fn () => array_map(
                fn (string $host) => '^'.preg_quote($host).'$',
                config('app.trusted_hosts'),
            ),
            subdomains: false,
        );

        // A display preference written by resources/js/app.js. It is only ever
        // compared with the literal "collapsed", so it does not need encrypting.
        $middleware->encryptCookies(except: ['ims_sidebar']);

        $middleware->web(append: [
            SetSecurityHeaders::class,
            EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
