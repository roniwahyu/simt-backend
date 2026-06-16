<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tahfiz_records', function (Blueprint $table) {
            $table->string('id', 50)->primary();
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
    }

    public function down(): void
    {
        Schema::dropIfExists('tahfiz_records');
    }
};
