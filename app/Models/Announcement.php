<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Announcement extends Model
{
    use HasFactory, SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'title',
        'content',
        'category',
        'is_pinned',
        'created_by',
        'published_at',
        'expires_at',
    ];

    protected $casts = [
        'is_pinned' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabel(): string
    {
        return match ($this->category) {
            'UMUM' => 'Umum',
            'AKADEMIK' => 'Akademik',
            'KEGIATAN' => 'Kegiatan',
            'KEUANGAN' => 'Keuangan',
            'URGENT' => 'Penting',
            default => $this->category,
        };
    }

    public function getCategoryColor(): string
    {
        return match ($this->category) {
            'UMUM' => 'gray',
            'AKADEMIK' => 'blue',
            'KEGIATAN' => 'green',
            'KEUANGAN' => 'yellow',
            'URGENT' => 'red',
            default => 'gray',
        };
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null && $this->published_at->isPast();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isVisibile(): bool
    {
        return $this->isPublished() && !$this->isExpired();
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeNotExpired($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now());
        });
    }

    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }
}
