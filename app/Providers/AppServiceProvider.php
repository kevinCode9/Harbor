<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Add this condition to prevent Pail crashes when --no-dev is used
        if ($this->app->isLocal() && class_exists(\Laravel\Pail\PailServiceProvider::class)) {
            $this->app->register(\Laravel\Pail\PailServiceProvider::class);
        }
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (env('APP_ENV') === 'production' && env('FORCE_HTTPS', true)) {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }
    }
}