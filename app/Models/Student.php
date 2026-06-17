<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use App\Traits\Auditable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Support\Facades\Hash;

class Student extends Authenticatable
{
    use BelongsToTenant, Auditable, HasApiTokens;

    protected $fillable = [
        'tenant_id', 'nis', 'nisn', 'nisn_bindex', 'name', 'gender', 'birth_date', 'birth_place', 'address', 'status',
        'photo', 'father_name', 'father_phone', 'mother_name', 'mother_phone', 'parent_email', 'student_password'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    /**
     * Auto-generate nisn_bindex saat saving model
     * untuk kepatuhan UU PDP No.27/2022
     */
    protected static function booted(): void
    {
        static::saving(function ($student) {
            if ($student->isDirty('nisn')) {
                $student->nisn_bindex = $student->nisn
                    ? hash_hmac('sha256', $student->nisn, config('app.key'))
                    : null;
            }
        });
    }

    public function guardians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'guardian_student', 'student_id', 'user_id')
            ->withPivot('relation')
            ->withTimestamps();
    }

    public function classes(): BelongsToMany
    {
        return $this->belongsToMany(SchoolClass::class, 'class_student', 'student_id', 'class_id')
            ->withPivot('school_year_id')
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function bills(): HasMany
    {
        return $this->hasMany(Bill::class, 'student_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class, 'student_id');
    }

    public function violations(): HasMany
    {
        return $this->hasMany(StudentViolation::class, 'student_id');
    }

    public function achievements(): HasMany
    {
        return $this->hasMany(StudentAchievement::class, 'student_id');
    }

    public function tahfizRecords(): HasMany
    {
        return $this->hasMany(TahfizRecord::class, 'student_id');
    }

    public function gradeDetails(): HasMany
    {
        return $this->hasMany(GradeDetail::class, 'student_id');
    }

    public function currentClass(): ?SchoolClass
    {
        $activeYear = SchoolYear::where('tenant_id', $this->tenant_id)->where('is_active', true)->first();
        if (! $activeYear) return null;

        return $this->classes()
            ->wherePivot('school_year_id', $activeYear->id)
            ->first();
    }

    public function isSuperAdmin(): bool
    {
        return false;
    }

    /**
     * Mutator to automatically hash student password
     */
    public function setStudentPasswordAttribute($value): void
    {
        if (!empty($value)) {
            $this->attributes['student_password'] = Hash::needsRehash($value) ? Hash::make($value) : $value;
        }
    }
}

