# DEV REPORT — SIMT Pembersihan Kode Monolitik & Restabilisasi Arsitektur Modular

## Sistem Informasi Manajemen Terpadu — Madrasah Tsanawiyah

**Tanggal:** 15 Juni 2026  
**Versi:** 1.1 (Pembersihan & Penyelarasan Fitur Baru)  
**Status:** Rekonsiliasi Selesai — Arsitektur Modular Pulih  
**Referensi:** Dokumen Analisis `DEV_DOCS/docs_sim/77` & `78`

---

## DAFTAR ISI

1. [Latar Belakang & Ringkasan Eksekusi](#1-latar-belakang--ringkasan-eksekusi)
2. [Detail Pembersihan Berkas (Hapus)](#2-detail-pembersihan-berkas-hapus)
3. [Restrukturisasi & Penyelarasan Fitur (Refactor)](#3-restrukturisasi--penyelarasan-fitur-refactor)
4. [Skema Database Baru (Batch 2 Migrations)](#4-skema-database-baru-batch-2-migrations)
5. [Laporan Verifikasi & Pengujian](#5-laporan-verifikasi--pengujian)

---

## 1. Latar Belakang & Ringkasan Eksekusi

Pada tanggal 15 Juni 2026, terdeteksi adanya penambahan berkas monolitik baru (*untracked*) di root `app/`, `config/`, dan `database/migrations/` (Set B) yang bertentangan dengan arsitektur **Modular Multi-Tenant (Nwidart)** yang berjalan pada cabang `main` (Set A).

Konflik ini berisiko memicu crash runtime instan karena:
1. Penggunaan nama tabel master yang bertabrakan (`classrooms` vs `school_classes`, `academic_years` vs `school_years`).
2. Duplikasi tabel core yang sudah ada di database (`students`, `attendances`, `payments`).
3. Penggunaan middleware dynamic scoping yang memanggil kelas model non-existent.

Melalui koordinasi dengan pengguna dan berdasarkan dokumen panduan **DEV_DOCS 77 & 78**, tindakan pembersihan, restrukturisasi, dan stabilisasi sistem telah berhasil dilaksanakan dengan hasil 100% sukses.

---

## 2. Detail Pembersihan Berkas (Hapus)

Berkas-berkas monolitik redundan berikut telah dihapus dari repositori:

*   **Database Migrations (Duplikat & Konflik):**
    *   `database/migrations/0003_create_academic_years_table.php` (Digantikan oleh `school_years` core)
    *   `database/migrations/0004_create_classrooms_table.php` (Digantikan oleh `school_classes` core)
    *   `database/migrations/0006_create_students_table.php` (Tabel `students` sudah dibuat di Batch 1)
    *   `database/migrations/0007_create_attendances_table.php` (Tabel `attendances` sudah dibuat di Batch 1)
    *   `database/migrations/0009_create_payments_table.php` (Tabel `payments` sudah dibuat di Batch 1)
    *   `database/migrations/0011_create_whatsapp_configs_table.php` (Digantikan oleh tabel modular `wa_notifications`)
*   **Http Middlewares (Redundan):**
    *   `app/Http/Middleware/CheckModuleSubscription.php`
    *   `app/Http/Middleware/SetTenantContext.php`
    *   `app/Http/Middleware/TenantScope.php`
*   **Models & Controllers (Redundan & Tidak Kompatibel):**
    *   `app/Models/WhatsappConfig.php`
    *   `app/Http/Controllers/DashboardController.php`
    *   `app/Http/Controllers/StudentController.php`
    *   `app/Http/Controllers/AttendanceController.php`
    *   `app/Http/Controllers/PaymentController.php`
    *   `app/Http/Controllers/WhatsappController.php`
*   **Unused Module Folders:**
    *   Direktori modul `Modules/Nilai/` yang tidak terpakai/aktif telah dibersihkan sepenuhnya.

---

## 3. Restrukturisasi & Penyelarasan Fitur (Refactor)

Fitur baru (Mata Pelajaran, Nilai Siswa, Pengumuman) telah diselaraskan dengan arsitektur modular multi-tenant core sebagai berikut:

### 3.1 Model & Konfigurasi Domain
*   **Fitur Mata Pelajaran (*Subjects*):** Membuat model baru [Subject.php](file:///d:/laragon/www/simt-backend/app/Models/Subject.php) dengan trait `BelongsToTenant` agar kueri tersaring otomatis berdasarkan tenant aktif.
*   **Fitur Nilai Siswa (*Grades*):** Memperbarui [Grade.php](file:///d:/laragon/www/simt-backend/app/Models/Grade.php) untuk menggunakan `BelongsToTenant` dan mengaitkannya ke tabel core.
*   **Fitur Pengumuman (*Announcements*):** Memperbarui [Announcement.php](file:///d:/laragon/www/simt-backend/app/Models/Announcement.php) dengan `BelongsToTenant`.
*   **Berkas Konfigurasi [simt.php](file:///d:/laragon/www/simt-backend/config/simt.php):** Dibersihkan dari array modul dan pengaturan WhatsApp monolitik. File ini sekarang hanya menyimpan parameter murni seperti `passing_grade`, Dapodik/EMIS integration, dan Midtrans/Xendit settings.

### 3.2 Pemindahan Controller ke Nwidart Modules
*   **`AnnouncementController.php`** ➔ Dipindahkan ke modul `Notification` ([AnnouncementController.php](file:///d:/laragon/www/simt-backend/Modules/Notification/app/Http/Controllers/AnnouncementController.php)). Namespace diperbarui menjadi `Modules\Notification\Http\Controllers` dan mewarisi base controller core.
*   **`GradeController.php`** ➔ Dipindahkan ke modul `Student` ([GradeController.php](file:///d:/laragon/www/simt-backend/Modules/Student/app/Http/Controllers/GradeController.php)). Namespace diperbarui menjadi `Modules\Student\Http\Controllers`. Kueri diselaraskan menggunakan relasi pivot kelas core `SchoolClass` dan `SchoolYear`.

---

## 4. Skema Database Baru (Batch 2 Migrations)

Berkas migrasi berikut berhasil disesuaikan dan dijalankan pada database:

1.  **`0005_create_subjects_table.php` [RAN]**
    *   *Perubahan:* Mengubah kolom `classroom_id` menjadi `school_class_id` yang terhubung secara valid ke foreign key tabel core `school_classes`.
2.  **`0008_create_grades_table.php` [RAN]**
    *   *Perubahan:* Menyimpan data nilai per mata pelajaran per siswa di level tenant.
3.  **`0010_create_announcements_table.php` [RAN]**
    *   *Perubahan:* Menyimpan data pengumuman tenant sekolah.

---

## 5. Laporan Verifikasi & Pengujian

Seluruh proses di atas telah divalidasi dengan hasil sebagai berikut:

### 5.1 Validasi Sintaks PHP (Linting)
Semua berkas PHP baru dan hasil modifikasi bebas dari kesalahan sintaks:
```powershell
No syntax errors detected in app/Models/Announcement.php
No syntax errors detected in app/Models/Grade.php
No syntax errors detected in app/Models/Subject.php
No syntax errors detected in Modules/Notification/app/Http/Controllers/AnnouncementController.php
No syntax errors detected in Modules/Student/app/Http/Controllers/GradeController.php
No syntax errors detected in config/simt.php
```

### 5.2 Status Migrasi Database
Verifikasi status migrasi menunjukkan seluruh tabel baru berada pada batch eksekusi nomor **2**:
```powershell
0005_create_subjects_table ................................................................................. [2] Ran  
0008_create_grades_table ................................................................................... [2] Ran  
0010_create_announcements_table ............................................................................ [2] Ran  
```

### 5.3 Optimasi Autoload & Routing List
*   Autoload cache berhasil diregenerasi menggunakan PHP 8.3 CLI:
    `Generated optimized autoload files containing 8533 classes`
*   Pemanggilan `php83 artisan route:list` berjalan lancar tanpa error, menunjukkan aplikasi Laravel berhasil mem-boot modular configuration secara utuh dan aman.
