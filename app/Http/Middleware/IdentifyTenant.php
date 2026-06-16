<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * IdentifyTenant — Identifikasi tenant dari header/subdomain/host
 *
 * Prioritas:
 * 1. Header X-Tenant-Domain (API / Next.js)
 * 2. Subdomain dari Host (Blade web)
 * 3. Fallback: authenticated user's tenant (super-admin panel routes)
 *
 * 🔒 PENTING: Middleware ini HARUS jalan SEBELUM SubstituteBindings
 *    (di-set di bootstrap/app.php dengan middleware priority)
 */
class IdentifyTenant
{
    public function __construct(
        protected Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $tenant = null;

        // 1. Priority: Header X-Tenant-Domain (API / Next.js)
        $domain = $request->header('X-Tenant-Domain');

        // 2. Fallback: subdomain from Host (Blade web)
        if (! $domain) {
            $host = $request->getHost();
            $parts = explode('.', $host);
            if (count($parts) >= 3) {
                $domain = $parts[0];
            }
        }

        // 3. Fallback: authenticated user's tenant (for super-admin panel routes)
        if (! $domain && $request->user()) {
            $tenant = $request->user()->tenant;
            if ($tenant) {
                $this->tenancy->setTenant($tenant);
                return $next($request);
            }
        }

        if ($domain) {
            $tenant = Tenant::where('domain', $domain)->where('status', 'active')->first();
        }

        if (! $tenant) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'TENANT_NOT_FOUND',
                    'message' => 'Tenant tidak ditemukan atau tidak aktif.',
                ], 400);
            }
            abort(400, 'Tenant tidak ditemukan atau tidak aktif.');
        }

        $this->tenancy->setTenant($tenant);

        // [2026-06-16 | AG] Penguatan Keamanan Multi-Tenant: Validasi silang User Session dengan Tenant ID
        if ($request->user() && !$request->user()->isSuperAdmin() && $request->user()->tenant_id !== $tenant->id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'TENANT_ACCESS_DENIED',
                    'message' => 'Unauthorized Tenant Access',
                ], 403);
            }
            abort(403, 'Unauthorized Tenant Access');
        }

        return $next($request);
    }
}
