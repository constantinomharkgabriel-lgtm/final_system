<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use App\Models\ClientRequest;
use App\Policies\ClientRequestPolicy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (app()->environment('production')) {
    URL::forceScheme('https');

    if (filled(config('app.url'))) {
        URL::forceRootUrl(config('app.url'));
    }
} else {
    URL::forceScheme('http');
    URL::forceRootUrl('http://127.0.0.1:8000');
}

        // Register policies
        Gate::policy(ClientRequest::class, ClientRequestPolicy::class);

        // Enable lazy loading prevention in development for better query awareness
        if (app()->isLocal()) {
            \Illuminate\Database\Eloquent\Model::preventLazyLoading();
            \Illuminate\Database\Eloquent\Model::preventAccessingMissingAttributes();
        }

        // Disable query logging in local for performance
        if (app()->isLocal() && app()->runningInConsole() === false) {
            \Illuminate\Support\Facades\DB::enableQueryLog();
        }

        // Set JSON response caching macro
        \Illuminate\Support\Facades\Response::macro('cache', function ($ttl = 3600) {
            return response()->setCache([
                'public' => true,
                'max_age' => $ttl,
                's_maxage' => $ttl,
            ]);
        });
    }
}
