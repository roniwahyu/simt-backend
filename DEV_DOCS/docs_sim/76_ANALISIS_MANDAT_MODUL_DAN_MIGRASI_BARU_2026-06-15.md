# 📊 LAPORAN ANALISIS: MANDAT MODUL BARU & MIGRASI UNTRACKED

**Tanggal:** 15 Juni 2026  
**Waktu:** 23:06 WITA (Local Time)  
**Status:** Rekomendasi Arsitektur & Tindakan Cepat  
**Topik:** Evaluasi Scaffold Modul Baru (Commit `89ea147`) & 11 Migrasi Untracked (`0001_` s.d. `0011_`)

---

## 1. Analisis Mandat Modul Baru (Commit `89ea147`)

Commit `89ea147` menscaffold 13 modul baru: `Akademik`, `Alumni`, `Berita`, `Dapur`, `Kantin`, `Keuangan`, `Nilai`, `Pegawai`, `Pendaftaran`, `Perpustakaan`, `Persuratan`, `Ppdb`, `Presensi`.

### 1.1 Status Mandat dalam Dokumen Analisis (DEV_DOCS)
1. **Rencana Asal (13 Modul ERP Raksasa)**:
   Modul-modul ini awalnya terdaftar dalam [02_analisis_kebutuhan.md](file:///d:/laragon/www/simt-backend/DEV_DOCS/docs_sim/02_analisis_kebutuhan.md) sebagai peta jalan jangka panjang sekolah terpadu.
2. **Mandat Pivot Strategis MVP (Doc 31 & 62)**:
   Berdasarkan [31_micro_saas_critical_swot_analysis.md](file:///d:/laragon/www/simt-backend/DEV_DOCS/docs_sim/31_micro_saas_critical_swot_analysis.md) dan [62_PEMETAAN_13_MODUL.md](file:///d:/laragon/www/simt-backend/DEV_DOCS/docs_sim/62_PEMETAAN_13_MODUL.md), budget Rp 5.000.000 dan waktu 3 bulan membuat pengembangan 13 modul menjadi **mustahil**. Mandat resminya adalah mem-pivot sistem menjadi **4 Modul Inti MVP** (`Core`, `Student`, `Attendance`, `Finance`).
3. **Modul Tambahan = Backlog Post-MVP**:
   Modul e-Office/Persuratan, Perpustakaan, Pegawai, dll, secara resmi ditangguhkan sebagai **Fase 3 Backlog (Tahun 2027)**.

### 1.2 Masalah Struktur & Risiko Runtime Modul Baru
Scaffold modul baru pada `89ea147` memiliki beberapa cacat arsitektur serius:
* **Pelanggaran Konvensi Folder (Doc Panduan)**: 
  Modul baru menempatkan Http Controller langsung di `Modules/NamaModul/Http/` dan Routes di `Modules/NamaModul/Routes/`. 
  Mandat di [PANDUAN_BUAT_MODUL_PLUGNPLAY.md](file:///d:/laragon/www/simt-backend/DEV_DOCS/docs_sim/PANDUAN_BUAT_MODUL_PLUGNPLAY.md) mewajibkan struktur kelas PHP ditaruh di dalam subfolder `app/` (seperti `Modules/NamaModul/app/Http/Controllers/`).
* **Potensi Runtime Crash (Fatal)**:
  `module.json` mereferensikan kelas `Modules\NamaModul\Providers\NamaModulServiceProvider` yang **tidak ada** di disk. Selain itu, namespace modul belum didaftarkan di `autoload.psr-4` pada [composer.json](file:///d:/laragon/www/simt-backend/composer.json) root. Jika diaktifkan, Laravel akan langsung crash saat *booting*.
* **Bypass Multi-Tenant Security**:
  Rute di [Routes/web.php](file:///d:/laragon/www/simt-backend/Modules/Persuratan/Routes/web.php) tidak menggunakan middleware group multi-tenant dan subscription gating (`['auth', SetTenantFromUser::class, 'module.active:NamaModul']`).
* **Redundansi Modul**:
  Terdapat modul `Keuangan` dan `Presensi` yang redundan/tumpang tindih dengan modul inti yang sudah fungsional (`Finance` dan `Attendance`).

---

## 2. Analisis Migrasi Baru (*Untracked* `0001_` s.d. `0011_`)

Sebanyak 11 file migrasi baru bernomor `0001_` s.d. `0011_` ditemukan tidak terlacak (*untracked*) di folder `database/migrations/`.

### 2.1 Status Mandat dalam Dokumen Analisis (DEV_DOCS)
* **Penilaian/Grades & Mata Pelajaran (Doc 68 & 65)**:
  Mandat penambahan tabel `subjects` dan `grades` dibahas dalam [68_SPRINT5_AKAN_DILAKUKAN.md](file:///d:/laragon/www/simt-backend/DEV_DOCS/docs_sim/68_SPRINT5_AKAN_DILAKUKAN.md) (Opsi C: SIM Akademik Lite) untuk mendukung portal orang tua/wali agar bisa melihat nilai ulangan siswa per mata pelajaran.
* **Konfigurasi WhatsApp Gateway (Doc 75)**:
  Tabel `whatsapp_configs` mendukung strategi WhatsApp Gateway stateful (Baileys) untuk menyimpan QR Code dan status koneksi per-tenant.

### 2.2 Bahaya & Konflik Teknis pada Skema Migrasi Baru
Menjalankan migrasi ini langsung (`php83 artisan migrate`) akan merusak database karena:
1. **Breaking Changes Nama Tabel Master**:
   * Sistem saat ini menggunakan tabel `school_years` (model [SchoolYear](file:///d:/laragon/www/simt-backend/app/Models/SchoolYear.php)) dan `school_classes` (model [SchoolClass](file:///d:/laragon/www/simt-backend/app/Models/SchoolClass.php)).
   * Migrasi baru membuat tabel `academic_years` dan `classrooms`. 
   * Perubahan ini mematahkan semua relasi Eloquent dan query pada file controller dan model core.
2. **Duplikasi Tabel Inti (Table Already Exists)**:
   * File `0001_create_tenants_table.php` dan `0002_create_users_table.php` akan memicu error fatal saat migrasi karena tabel `tenants` dan `users` sudah dibuat sebelumnya oleh migrasi core Set A.
3. **Format ID Kolom**:
   * Menggunakan format auto-increment integer ID konvensional yang perlu disesuaikan foreign key-nya ke tabel core yang sedang aktif.

---

## 3. Rekomendasi Rencana Tindakan

### Langkah 1: Pembersihan & Perbaikan Migrasi (Database)
- **Hapus file migrasi duplikat** yang menabrak tabel master core:
  - `0001_create_tenants_table.php`
  - `0002_create_users_table.php`
  - `0003_create_academic_years_table.php` (Gunakan `school_years` yang sudah ada)
  - `0004_create_classrooms_table.php` (Gunakan `school_classes` yang sudah ada)
  - `0006_create_students_table.php`
  - `0007_create_attendances_table.php`
  - `0009_create_payments_table.php`
- **Sesuaikan & Terapkan Migrasi Fitur Baru**:
  - `0005_create_subjects_table.php` ➔ Ubah target foreign key `classroom_id` menjadi `school_class_id` yang terhubung ke tabel `school_classes`.
  - `0008_create_grades_table.php` ➔ Pertahankan untuk fitur nilai siswa.
  - `0010_create_announcements_table.php` ➔ Pertahankan untuk fitur pengumuman.
  - `0011_create_whatsapp_configs_table.php` ➔ Pertahankan untuk session gateway.

### Langkah 2: Pembersihan & Restrukturisasi Modul Baru
- **Hapus Modul Redundan**:
  - `Modules/Keuangan` (Gunakan modul [Modules/Finance](file:///d:/laragon/www/simt-backend/Modules/Finance))
  - `Modules/Presensi` (Gunakan modul [Modules/Attendance](file:///d:/laragon/www/simt-backend/Modules/Attendance))
- **Restrukturisasi Modul Valid (misal: Persuratan)**:
  - Pindahkan isi folder `Http` ke `app/Http`.
  - Ubah `Routes/` menjadi `routes/`.
  - Buat folder `app/Providers/` dan tambahkan Service Provider yang diperlukan.
  - Daftarkan namespace baru pada [composer.json](file:///d:/laragon/www/simt-backend/composer.json) root dan jalankan `composer dump-autoload --optimize --no-scripts` menggunakan PHP 8.3 (`php83`).
