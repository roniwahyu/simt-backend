<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id', 'nis', 'nisn', 'name', 'gender', 'birth_date', 'birth_place', 'address', 'status'
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

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

    public function currentClass(): ?SchoolClass
    {
        $activeYear = SchoolYear::where('tenant_id', $this->tenant_id)->where('is_active', true)->first();
        if (! $activeYear) return null;

        return $this->classes()
            ->wherePivot('school_year_id', $activeYear->id)
            ->first();
    }
}
