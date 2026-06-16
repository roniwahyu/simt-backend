<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bill extends Model
{
    use BelongsToTenant, Auditable;

    protected $fillable = [
        'tenant_id', 'student_id', 'period', 'component', 'amount', 'paid_amount', 'discount', 'status', 'due_date'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'due_date' => 'date',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'bill_id');
    }

    public function remaining(): float
    {
        return max(0, (float) $this->amount - (float) $this->paid_amount - (float) $this->discount);
    }

    public function updateStatus(): void
    {
        $remaining = $this->remaining();
        if ($remaining <= 0) {
            $this->status = 'paid';
        } elseif ($this->paid_amount > 0 || $this->discount > 0) {
            $this->status = 'partial';
        } else {
            $this->status = 'unpaid';
        }
        $this->saveQuietly();
    }
}
