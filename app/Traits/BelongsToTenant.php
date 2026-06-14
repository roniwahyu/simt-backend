<?php

namespace App\Traits;

use App\Support\Tenancy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * BelongsToTenant — Global scope + auto-fill tenant_id
 *
 * Semua model domain menggunakan trait ini untuk:
 * 1. Auto-fill tenant_id saat creating (dari Tenancy singleton)
 * 2. Auto-filter query berdasarkan tenant_id (global scope)
 *
 * 🔒 KRITIS: Tenancy::tenantId() membaca dari singleton.
 * Jika Tenancy bukan singleton, global scope TIDAK bekerja!
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::creating(function (Model $model) {
            if (! $model->getAttribute('tenant_id')) {
                $tenantId = app(Tenancy::class)->tenantId();
                if ($tenantId) {
                    $model->setAttribute('tenant_id', $tenantId);
                }
            }
        });

        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = app(Tenancy::class)->tenantId();
            if ($tenantId) {
                $builder->where($builder->getModel()->getTable() . '.tenant_id', $tenantId);
            }
        });
    }

    public function tenant()
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function scopeWithoutTenant(Builder $query): Builder
    {
        return $query->withoutGlobalScope('tenant');
    }

    public function scopeForTenant(Builder $query, $tenantId): Builder
    {
        return $query->withoutGlobalScope('tenant')->where('tenant_id', $tenantId);
    }
}
