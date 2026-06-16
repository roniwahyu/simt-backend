<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TahfizRecord extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'date',
        'surah',
        'ayah_start',
        'ayah_end',
        'type',
        'score',
        'fluency',
        'note',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'datetime',
        'ayah_start' => 'integer',
        'ayah_end' => 'integer',
        'score' => 'float',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
