<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('nis', 50)->nullable(); // NIS lokal
            $table->string('nisn', 50)->nullable(); // NISN nasional
            $table->string('name', 255);
            $table->string('gender', 10)->nullable(); // L / P
            $table->date('birth_date')->nullable();
            $table->string('birth_place', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('status', 20)->default('active'); // active, inactive, graduated, transferred
            $table->timestamps();
            // 🔒 Integritas data: NIS & NISN HARUS unik per-tenant.
            // NULL diperlakukan distinct oleh SQLite & MySQL, jadi siswa tanpa
            // NIS/NISN tetap boleh banyak; duplikat non-null DITOLAK di level DB.
            $table->unique(['tenant_id', 'nis'], 'students_tenant_nis_unique');
            $table->unique(['tenant_id', 'nisn'], 'students_tenant_nisn_unique');
        });

        Schema::create('class_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('school_year_id')->constrained('school_years')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['student_id', 'class_id', 'school_year_id']);
        });

        Schema::create('guardian_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // wali = user with role wali
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->string('relation', 50)->default('ayah'); // ayah, ibu, wali
            $table->timestamps();
            $table->unique(['user_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guardian_student');
        Schema::dropIfExists('class_student');
        Schema::dropIfExists('students');
    }
};
