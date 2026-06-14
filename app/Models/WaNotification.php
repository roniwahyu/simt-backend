<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class WaNotification extends Model
{
    use BelongsToTenant;

    protected $table = 'wa_notifications';

    protected $fillable = [
        'tenant_id', 'to_phone', 'type', 'payload', 'status', 'attempts', 'last_error', 'sent_at'
    ];

    protected $casts = [
        'payload' => 'array',
        'attempts' => 'integer',
        'sent_at' => 'datetime',
    ];
}
