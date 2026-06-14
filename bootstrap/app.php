<?php

use App\Http\Middleware\CheckTenantAccess;
use App\Http\Middleware\EnsureModuleActive;
use App\Http\Middleware\IdentifyTenant;
use App\Http\Middleware\SetTenantFromUser;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
            'identify.tenant' => IdentifyTenant::class,
            'set.tenant.user' => SetTenantFromUser::class,
            'check.tenant.access' => CheckTenantAccess::class,
            'module.active' => EnsureModuleActive::class,
        ]);

        // 🔒 KRITIS: Tenant middleware HARUS jalan SEBELUM SubstituteBindings
        // Tanpa ini, route model binding bocor lintas tenant
        $middleware->priority([
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            IdentifyTenant::class,
            SetTenantFromUser::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
