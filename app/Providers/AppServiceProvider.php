<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockTransaction;
use App\Models\Supplier;
use App\Models\User;
use App\Services\InventoryStatistics;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
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

        // Polymorphic columns (activity_logs.subject_type) store these short names
        // instead of PHP class names, so renaming a class never breaks stored data.
        Relation::enforceMorphMap([
            'user' => User::class,
            'category' => Category::class,
            'product' => Product::class,
            'supplier' => Supplier::class,
            'stock_transaction' => StockTransaction::class,
        ]);

        Password::defaults(function () {
            $rule = Password::min(10)->letters()->mixedCase()->numbers();

            // Rejects passwords found in public data breaches (needs outbound HTTPS).
            return $this->app->isProduction() ? $rule->uncompromised() : $rule;
        });

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        $this->defineGates();

        // The sidebar badge reads the cached dashboard figures, so it costs no extra queries.
        View::composer('partials.sidebar-nav', function ($view) {
            $view->with('stockAlertCount', app(InventoryStatistics::class)->alertCount());
        });
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
