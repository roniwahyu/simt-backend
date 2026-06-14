<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * EnsureModuleActive — Cek apakah modul aktif untuk tenant saat ini
 *
 * Usage di route: ->middleware('module.active:Student')
 * Jika modul tidak aktif → 403 MODULE_INACTIVE
 */
class EnsureModuleActive
{
    public function __construct(
        protected Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next, string $moduleCode): Response
    {
        $tenant = $this->tenancy->tenant();

        if (! $tenant || ! $tenant->hasModule($moduleCode)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'MODULE_INACTIVE',
                    'message' => "Modul '{$moduleCode}' tidak aktif untuk tenant ini.",
                ], 403);
            }
            abort(403, "Modul '{$moduleCode}' tidak aktif.");
        }

        return $next($request);
    }
}
