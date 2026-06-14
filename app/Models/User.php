<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

/**
 * User — TIDAK pakai BelongsToTenant global scope
 *
 * 🔒 Alasan: Autentikasi terjadi SEBELUM konteks tenant di-set.
 * Isolasi user per tenant dilakukan via middleware, bukan global scope.
 */
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $guard_name = 'web';

    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'phone',
        'password',
        'role_display',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function guardianStudents(): BelongsToMany
    {
        return $this->belongsToMany(Student::class, 'guardian_student', 'user_id', 'student_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    public function waNotifications(): HasMany
    {
        return $this->hasMany(WaNotification::class, 'tenant_id', 'tenant_id');
    }

    public function isSuperAdmin(): bool
    {
        return $this->tenant_id === null && $this->hasRole('superadmin');
    }

    /**
     * Scope: filter user berdasarkan tenant (manual, bukan global scope)
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }
}
