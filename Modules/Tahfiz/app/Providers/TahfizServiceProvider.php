<?php

namespace Modules\Tahfiz\Providers;

use Illuminate\Support\ServiceProvider;

class TahfizServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register RouteServiceProvider for route mapping
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        // Load module views
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tahfiz');
    }
}
