<?php

namespace App\Providers;

use App\Support\Tenancy;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // 🔒 KRITIS: Tenancy HARUS singleton!
        // Tanpa ini, app(Tenancy::class) mengembalikan instance baru setiap resolve
        // → global scope BelongsToTenant TIDAK memfilter data antar tenant.
        $this->app->singleton(Tenancy::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
