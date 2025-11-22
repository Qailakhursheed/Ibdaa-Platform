<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

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
        $this->configureRateLimiting();
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        // API Rate Limiting - 100 requests per minute for authenticated users
        RateLimiter::for('api', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(100)->by($request->user()->user_id)
                : Limit::perMinute(20)->by($request->ip());
        });

        // Login attempts - 5 attempts per minute
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinute(5)->by($request->ip());
        });

        // Heavy operations (file uploads, imports) - 10 per minute
        RateLimiter::for('heavy', function (Request $request) {
            return $request->user()
                ? Limit::perMinute(10)->by($request->user()->user_id)
                : Limit::perMinute(2)->by($request->ip());
        });
    }
}
