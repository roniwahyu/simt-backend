<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop unique index
            $table->dropUnique('students_tenant_nisn_unique');
            
            // Change column types to text to hold base64-encoded encrypted cipher text
            $table->text('nisn')->nullable()->change();
            $table->text('birth_date')->nullable()->change();
            $table->text('birth_place')->nullable()->change();
            $table->text('gender')->nullable()->change();

            // Add blind index for exact match search & unique validation
            $table->string('nisn_bindex', 64)->nullable()->after('nisn');
            
            // Add unique index on blind index per-tenant
            $table->unique(['tenant_id', 'nisn_bindex'], 'students_tenant_nisn_bindex_unique');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropUnique('students_tenant_nisn_bindex_unique');
            $table->dropColumn('nisn_bindex');

            $table->string('nisn', 50)->nullable()->change();
            $table->date('birth_date')->nullable()->change();
            $table->string('birth_place', 100)->nullable()->change();
            $table->string('gender', 10)->nullable()->change();

            $table->unique(['tenant_id', 'nisn'], 'students_tenant_nisn_unique');
        });
    }
};
