<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'student_id',
        'subject_id',
        'teacher_id',
        'type',
        'score',
        'description',
    ];

    protected $casts = [
        'score' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher()
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function getTypeLabel(): string
    {
        return match ($this->type) {
            'UH1' => 'Ulangan Harian 1',
            'UH2' => 'Ulangan Harian 2',
            'UH3' => 'Ulangan Harian 3',
            'UH4' => 'Ulangan Harian 4',
            'UH5' => 'Ulangan Harian 5',
            'UH6' => 'Ulangan Harian 6',
            'UTS' => 'Ujian Tengah Semester',
            'UAS' => 'Ujian Akhir Semester',
            'TUGAS1' => 'Tugas 1',
            'TUGAS2' => 'Tugas 2',
            'TUGAS3' => 'Tugas 3',
            'PRAKTIK' => 'Praktik',
            'SIKAP' => 'Sikap',
            default => $this->type,
        };
    }

    public function getCategoryType(): string
    {
        if (str_starts_with($this->type, 'UH')) {
            return 'Ulangan Harian';
        }
        if (str_starts_with($this->type, 'TUGAS')) {
            return 'Tugas';
        }
        if (in_array($this->type, ['UTS', 'UAS'])) {
            return 'Ujian';
        }
        return $this->type;
    }

    public function getGradePredicate(): string
    {
        if ($this->score >= 93) return 'A';
        if ($this->score >= 84) return 'B';
        if ($this->score >= 75) return 'C';
        return 'D';
    }

    public function getGradeDescription(): string
    {
        if ($this->score >= 93) return 'Sangat Baik';
        if ($this->score >= 84) return 'Baik';
        if ($this->score >= 75) return 'Cukup';
        return 'Kurang';
    }

    public function isPassing(): bool
    {
        return $this->score >= config('simt.passing_grade', 75);
    }

    public function scopeBySubject($query, int $subjectId)
    {
        return $query->where('subject_id', $subjectId);
    }

    public function scopeByStudent($query, int $studentId)
    {
        return $query->where('student_id', $studentId);
    }
}
