<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
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

        $this->defineGates();
    }

    /**
     * Abilities that are not tied to one model. Model-specific rules live in
     * app/Policies and are matched to their model automatically.
     */
    private function defineGates(): void
    {
        Gate::define('adjust-stock', fn (User $user) => $user->isAdmin());
        Gate::define('view-reports', fn (User $user) => $user->isAdmin());
        Gate::define('view-activity-log', fn (User $user) => $user->isAdmin());
    }
}
