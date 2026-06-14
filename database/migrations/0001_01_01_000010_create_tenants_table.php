<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('domain', 100)->unique();
            $table->string('phone', 20)->nullable();
            $table->string('address', 500)->nullable();
            $table->enum('status', ['prospect', 'contracted', 'active', 'grace_read', 'suspended', 'terminated'])->default('prospect');
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('grace_until')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::create('tenant_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('module_code', 50); // Core, Student, Attendance, Finance, Tahfiz, Inklusi
            $table->boolean('active')->default(false);
            $table->timestamp('active_until')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'module_code']);
        });

        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('period', 20); // e.g., 2026-1 (semester 1)
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['unpaid', 'paid', 'overdue', 'cancelled'])->default('unpaid');
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_method', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('tenants');
    }
};
