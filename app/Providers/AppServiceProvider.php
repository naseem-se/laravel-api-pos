<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        */

        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(120)
                ->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('auth', function (Request $request) {
            return Limit::perMinute(5)
                ->by($request->ip());
        });

        /*
        |--------------------------------------------------------------------------
        | Eloquent Strict Mode
        |--------------------------------------------------------------------------
        |
        | Detect N+1 queries, lazy loading and mass-assignment problems
        | during development.
        |
        */

        Model::shouldBeStrict(
            ! $this->app->isProduction()
        );

        /*
        |--------------------------------------------------------------------------
        | Force HTTPS in Production
        |--------------------------------------------------------------------------
        */

        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }
    }
}