<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * SetTenantFromUser — Set tenant context dari authenticated user
 *
 * Dijalankan setelah IdentifyTenant. Jika IdentifyTenant sudah set tenant (dari header/subdomain),
 * middleware ini memverifikasi bahwa user berhak mengakses tenant tersebut.
 * Jika IdentifyTenant belum set tenant, middleware ini set dari user->tenant_id.
 *
 * 🔒 PENTING: Middleware ini HARUS jalan SEBELUM SubstituteBindings
 */
class SetTenantFromUser
{
    public function __construct(
        protected Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            return $next($request);
        }

        $tenant = $this->tenancy->tenant();

        // Super-admin tanpa tenant_id bisa akses semua tenant
        if ($user->isSuperAdmin()) {
            if (! $tenant && $user->tenant_id) {
                $this->tenancy->setTenant($user->tenant);
            }
            return $next($request);
        }

        // User biasa: set tenant dari user->tenant jika belum diset
        if (! $tenant && $user->tenant_id) {
            $this->tenancy->setTenant($user->tenant);
            $tenant = $this->tenancy->tenant();
        }

        // Verifikasi: user hanya boleh mengakses tenant miliknya
        if ($tenant && $user->tenant_id && $tenant->id !== $user->tenant_id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'TENANT_ACCESS_DENIED',
                    'message' => 'Anda tidak memiliki akses ke tenant ini.',
                ], 403);
            }
            abort(403, 'Anda tidak memiliki akses ke tenant ini.');
        }

        return $next($request);
    }
}
