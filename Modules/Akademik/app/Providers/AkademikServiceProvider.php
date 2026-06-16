<?php

namespace Modules\Akademik\Providers;

use Illuminate\Support\ServiceProvider;

class AkademikServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'akademik');
    }
}
