<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Menambahkan kolom nisn_bindex (blind index) untuk pencarian NISN yang aman
     * sesuai kepatuhan UU PDP No.27/2022 tentang Perlindungan Data Pribadi.
     * 
     * Blind index memungkinkan pencarian eksak tanpa menyimpan NISN dalam bentuk plain text.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Tambah kolom nisn_bindex setelah kolom nisn
            $table->string('nisn_bindex', 64)->nullable()->after('nisn');
            
            // Unique constraint per tenant untuk mencegah duplikasi NISN
            $table->unique(['tenant_id', 'nisn_bindex'], 'students_tenant_nisn_bindex_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Hapus unique constraint
            $table->dropUnique('students_tenant_nisn_bindex_unique');
            
            // Hapus kolom nisn_bindex
            $table->dropColumn('nisn_bindex');
        });
    }
};
