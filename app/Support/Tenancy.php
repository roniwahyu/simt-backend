<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

/**
 * Tenancy — Singleton konteks tenant saat ini
 *
 * 🔒 KRITIS: Class ini HARUS di-bind sebagai singleton di AppServiceProvider.
 * Tanpa singleton, global scope BelongsToTenant TIDAK memfilter data antar tenant.
 *
 * Cara kerja:
 * 1. Middleware IdentifyTenant/SetTenantFromUser memanggil Tenancy::setTenant()
 * 2. BelongsToTenant trait membaca Tenancy::tenantId() untuk global scope
 * 3. Semua query model domain otomatis ter-filter berdasarkan tenant_id
 *
 * @see \App\Providers\AppServiceProvider::register() — singleton binding
 * @see \App\Traits\BelongsToTenant — global scope
 * @see \App\Http\Middleware\IdentifyTenant — set tenant dari header/subdomain
 * @see \App\Http\Middleware\SetTenantFromUser — set tenant dari user login
 */
class Tenancy
{
    protected ?Tenant $tenant = null;

    /**
     * Set tenant aktif (dipanggil oleh middleware)
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;

        // Set Spatie team context untuk RBAC per tenant
        if ($tenant && class_exists(\Spatie\Permission\PermissionRegistrar::class)) {
            app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
        }
    }

    /**
     * Get tenant aktif
     */
    public function tenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * Get tenant ID aktif (digunakan oleh BelongsToTenant global scope)
     */
    public function tenantId(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Check apakah tenant sudah di-set
     */
    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    /**
     * Initialize tenant dari authenticated user (fallback)
     */
    public function initializeFromAuth(): void
    {
        $user = Auth::user();
        if ($user && $user->tenant_id && !$this->hasTenant()) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                $this->setTenant($tenant);
            }
        }
    }

    /**
     * Forget tenant (untuk testing / cleanup)
     */
    public function forgetTenant(): void
    {
        $this->tenant = null;
    }
}
