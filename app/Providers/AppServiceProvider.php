<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Outside production, loading a relationship that was not eager loaded
        // throws an exception, so N+1 query problems show up during development and tests.
        Model::preventLazyLoading(! $this->app->isProduction());

        Paginator::useBootstrapFive();

        Password::defaults(function () {
            $rule = Password::min(10)->letters()->mixedCase()->numbers();

            // Rejects passwords found in public data breaches (needs outbound HTTPS).
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}
