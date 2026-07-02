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
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Reset auth guards after each request so that the Sanctum RequestGuard
        // does not cache the authenticated user across requests in the same process
        // (e.g. during feature tests where multiple HTTP requests share the same
        // application instance).
        $this->app->terminating(function () {
            $this->app->make('auth')->forgetGuards();
        });
    }
}
