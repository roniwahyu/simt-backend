<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add fields to students table
        Schema::table('students', function (Blueprint $table) {
            $table->string('photo', 255)->nullable()->after('address');
            $table->string('father_name', 255)->nullable()->after('photo');
            $table->string('father_phone', 50)->nullable()->after('father_name');
            $table->string('mother_name', 255)->nullable()->after('father_phone');
            $table->string('mother_phone', 50)->nullable()->after('mother_name');
            $table->string('parent_email', 255)->nullable()->after('mother_phone');
            $table->string('student_password', 255)->nullable()->after('parent_email');
        });

        // 2. Create schedules table
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 1 = Senin, ..., 7 = Minggu
            $table->unsignedTinyInteger('start_period'); // Jam ke-
            $table->unsignedTinyInteger('end_period'); // Jam ke-
            $table->foreignId('teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'class_id']);
        });

        // 3. Create student_violations table
        Schema::create('student_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->dateTime('date');
            $table->string('category', 50); // ringan, sedang, berat
            $table->text('description');
            $table->integer('points')->default(0);
            $table->string('action', 255)->nullable(); // sanksi / tindakan
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'student_id']);
        });

        // 4. Create student_achievements table
        Schema::create('student_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->dateTime('date');
            $table->string('title', 255);
            $table->string('category', 50); // akademik, non-akademik, keagamaan, olahraga, seni
            $table->string('level', 50); // kelas, sekolah, kecamatan, kota, provinsi, nasional, internasional
            $table->string('ranking', 100)->nullable(); // Juara 1, dll
            $table->text('description')->nullable();
            $table->string('certificate', 255)->nullable(); // path file sertifikat
            $table->timestamps();

            $table->index(['tenant_id', 'student_id']);
        });

        // 5. Create tahfiz_records table
        Schema::create('tahfiz_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->dateTime('date');
            $table->string('surah', 100);
            $table->unsignedInteger('ayah_start');
            $table->unsignedInteger('ayah_end');
            $table->string('type', 50); // ziyadah, murajaah
            $table->decimal('score', 5, 2)->default(0);
            $table->string('fluency', 50)->nullable(); // lancar, cukup, kurang
            $table->text('note')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'student_id']);
        });

        // 6. Create grade_details table
        Schema::create('grade_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained('subjects')->cascadeOnDelete();
            $table->string('category', 50); // TUGAS, HARIAN, UTS, UAS, AKHIR
            $table->string('title', 255); // Tugas 1, dll
            $table->decimal('score', 5, 2)->default(0);
            $table->decimal('weight', 5, 2)->default(1);
            $table->dateTime('date')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'student_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grade_details');
        Schema::dropIfExists('tahfiz_records');
        Schema::dropIfExists('student_achievements');
        Schema::dropIfExists('student_violations');
        Schema::dropIfExists('schedules');

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'photo',
                'father_name',
                'father_phone',
                'mother_name',
                'mother_phone',
                'parent_email',
                'student_password'
            ]);
        });
    }
};
