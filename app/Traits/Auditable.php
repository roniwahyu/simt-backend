<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Support\Tenancy;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function (Model $model) {
            static::logEvent($model, 'created', null, $model->getAttributes());
        });

        static::updated(function (Model $model) {
            $oldValues = array_intersect_key($model->getOriginal(), $model->getChanges());
            $newValues = $model->getChanges();

            // Saring field timestamp agar tidak menumpuk log
            unset($oldValues['updated_at'], $newValues['updated_at']);

            if (!empty($newValues)) {
                static::logEvent($model, 'updated', $oldValues, $newValues);
            }
        });

        static::deleted(function (Model $model) {
            static::logEvent($model, 'deleted', $model->getOriginal(), null);
        });
    }

    protected static function logEvent(Model $model, string $event, ?array $oldValues, ?array $newValues): void
    {
        $userId = Auth::id();
        $tenantId = app(Tenancy::class)->tenantId() ?? $model->getAttribute('tenant_id');
        $ip = request() ? request()->ip() : null;
        $userAgent = request() ? request()->userAgent() : null;

        DB::table('audit_logs')->insert([
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues ? json_encode($oldValues) : null,
            'new_values' => $newValues ? json_encode($newValues) : null,
            'ip_address' => $ip,
            'user_agent' => $userAgent,
            'created_at' => now(),
        ]);
    }
}
