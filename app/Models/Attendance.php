<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'student_id', 'class_id', 'date', 'status', 'arrival_time', 'notes', 'marked_by'
    ];

    protected $casts = [
        'date' => 'date',
        'arrival_time' => 'datetime:H:i',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id');
    }

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by');
    }

    public static function statusLabel(string $status): string
    {
        return match ($status) {
            'H' => 'Hadir',
            'A' => 'Alpa',
            'I' => 'Izin',
            'S' => 'Sakit',
            'T' => 'Terlambat',
            default => $status,
        };
    }
}
