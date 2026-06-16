<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToTenant;

    // The table associated with the model
    protected $table = 'audit_logs';

    // The attributes that are mass assignable
    protected $fillable = [
        'tenant_id',
        'user_id',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    // Disable default timestamps (since we only have created_at)
    public $timestamps = false;

    // Get the tenant that owns the audit log
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    // Get the user that performed the action
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Helper to get formatted model name
    public function getModelNameAttribute(): string
    {
        $parts = explode('\\', $this->auditable_type);
        return end($parts);
    }
}
