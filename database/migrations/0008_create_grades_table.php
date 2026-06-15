<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('student_id');
            $table->unsignedBigInteger('subject_id');
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->enum('type', [
                'UH1', 'UH2', 'UH3', 'UH4', 'UH5', 'UH6',
                'UTS', 'UAS',
                'TUGAS1', 'TUGAS2', 'TUGAS3',
                'PRAKTIK',
                'SIKAP',
            ]);
            $table->decimal('score', 5, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->timestamps();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('student_id')->references('id')->on('students')->cascadeOnDelete();
            $table->foreign('subject_id')->references('id')->on('subjects')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
            $table->unique(['tenant_id', 'student_id', 'subject_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grades');
    }
};
