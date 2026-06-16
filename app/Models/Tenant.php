<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'domain', 'phone', 'address', 'status', 'activated_at', 'grace_until', 'settings'];

    protected $casts = [
        'settings' => 'array',
        'activated_at' => 'datetime',
        'grace_until' => 'datetime',
    ];
    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'tenant_id');
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'tenant_id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(SchoolClass::class, 'tenant_id');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(TenantModule::class, 'tenant_id');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'tenant_id');
    }

    public function hasModule(string $moduleCode): bool
    {
        return $this->modules()->where('module_code', $moduleCode)->where('active', true)->exists();
    }

    public function isSuspended(): bool
    {
        return $this->status === 'suspended' || $this->status === 'terminated';
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
