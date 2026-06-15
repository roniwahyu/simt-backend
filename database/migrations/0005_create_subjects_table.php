<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('school_class_id');
            $table->string('name');
            $table->string('code', 20)->nullable();
            $table->unsignedTinyInteger('hours_per_week')->default(2);
            $table->unsignedBigInteger('teacher_id')->nullable();
            $table->enum('category', [
                'UMUM',
                'AGAMA_ISLAM',
                'MUATAN_LOKAL',
                'PENGEMBANGAN_DIRI',
                'EKSTRAKURIKULER',
            ])->default('UMUM');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
            $table->foreign('school_class_id')->references('id')->on('school_classes')->cascadeOnDelete();
            $table->foreign('teacher_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subjects');
    }
};
