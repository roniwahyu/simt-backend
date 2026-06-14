<?php

namespace App\Http\Middleware;

use App\Support\Tenancy;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * CheckTenantAccess — Verifikasi user punya akses ke tenant
 * (alias untuk compatibility, logic utama di SetTenantFromUser)
 */
class CheckTenantAccess
{
    public function __construct(
        protected Tenancy $tenancy,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && !$user->isSuperAdmin() && !$this->tenancy->hasTenant()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'code' => 'TENANT_NOT_FOUND',
                    'message' => 'Tenant tidak ditemukan.',
                ], 400);
            }
            abort(400, 'Tenant tidak ditemukan.');
        }

        return $next($request);
    }
}
