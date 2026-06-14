<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->date('date');
            $table->enum('status', ['H', 'A', 'I', 'S', 'T'])->default('H'); // Hadir, Alpa, Izin, Sakit, Terlambat
            $table->time('arrival_time')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('marked_by')->nullable()->constrained('users')->nullOnDelete(); // guru yang input
            $table->timestamps();
            $table->unique(['student_id', 'date'], 'attendance_unique_student_date');
            $table->index(['tenant_id', 'date']);
            $table->index(['tenant_id', 'class_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
