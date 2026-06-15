# 📋 RENCANA IMPLEMENTASI CEPAT: PEMBERSIHAN & RESTABILISASI ARSITEKTUR

**Tanggal:** 15 Juni 2026  
**Status:** Siap Dieksekusi (Menunggu Persetujuan)  
**Tujuan:** Mengembalikan repositori ke arsitektur Modular Multi-Tenant core dan mengintegrasikan fitur baru secara aman.

---

## 🛠️ DAFTAR TUGAS & CHECKLIST EKSEKUSI

### FASE 1: Pembersihan Berkas Monolitik (Hapus)
*Menghapus file untracked yang tidak kompatibel dengan arsitektur multi-tenant core.*

*   [ ] **Hapus Berkas Migrasi Duplikat:**
    *   `database/migrations/0003_create_academic_years_table.php` (Gunakan `school_years` core)
    *   `database/migrations/0004_create_classrooms_table.php` (Gunakan `school_classes` core)
    *   `database/migrations/0006_create_students_table.php` (Sudah ada tabel `students` core)
    *   `database/migrations/0007_create_attendances_table.php` (Sudah ada tabel `attendances` core)
    *   `database/migrations/0009_create_payments_table.php` (Sudah ada tabel `payments` core)
    *   `database/migrations/0011_create_whatsapp_configs_table.php` (Sudah ada tabel `wa_notifications` core)
*   [ ] **Hapus Middleware Redundan:**
    *   `app/Http/Middleware/CheckModuleSubscription.php`
    *   `app/Http/Middleware/SetTenantContext.php`
    *   `app/Http/Middleware/TenantScope.php`
*   [ ] **Hapus Model Redundan:**
    *   `app/Models/WhatsappConfig.php`
*   [ ] **Hapus Controller Redundan:**
    *   `app/Http/Controllers/DashboardController.php`
    *   `app/Http/Controllers/StudentController.php`
    *   `app/Http/Controllers/AttendanceController.php`
    *   `app/Http/Controllers/PaymentController.php`
    *   `app/Http/Controllers/WhatsappController.php`

---

### FASE 2: Penyelarasan & Pemindahan Fitur Baru (Pindahkan & Bersihkan)
*Menyelaraskan fitur pengumuman, nilai, dan mata pelajaran agar menggunakan relasi modular core.*

*   [ ] **Fitur Pengumuman (Announcements):**
    *   Pindahkan model `app/Models/Announcement.php` ke `Modules/Notification/app/Models/Announcement.php` dan sesuaikan namespace-nya.
    *   Pindahkan controller `app/Http/Controllers/AnnouncementController.php` ke `Modules/Notification/app/Http/Controllers/AnnouncementController.php` dan sesuaikan namespace-nya.
    *   Pertahankan berkas migrasi `database/migrations/0010_create_announcements_table.php`.
*   [ ] **Fitur Nilai Siswa (Grades):**
    *   Pindahkan model `app/Models/Grade.php` ke `Modules/Student/app/Models/Grade.php`.
    *   Pindahkan controller `app/Http/Controllers/GradeController.php` ke `Modules/Student/app/Http/Controllers/GradeController.php`.
    *   Sesuaikan kueri dan validasi di dalam model/controller agar merujuk ke tabel `school_classes` (bukan `classrooms`) dan `school_years` (bukan `academic_years`).
*   [ ] **Penyesuaian Migrasi Pending:**
    *   Edit `database/migrations/0005_create_subjects_table.php` ➔ Ganti kolom `classroom_id` menjadi `school_class_id` yang terhubung ke `school_classes`.
    *   Edit `database/migrations/0008_create_grades_table.php` ➔ Ganti foreign key `classroom_id`/`subject_id` agar konsisten dengan tabel core.

---

### FASE 3: Eksekusi & Pengujian (Jalankan)
*Menjalankan migrasi dan menguji fungsionalitas sistem.*

*   [ ] **Jalankan Migrasi Fitur Baru:**
    ```powershell
    php83 artisan migrate
    ```
*   [ ] **Optimasi Autoload Composer:**
    ```powershell
    composer dump-autoload --optimize
    ```
*   [ ] **Verifikasi Routing:**
    ```powershell
    php83 artisan route:list
    ```
*   [ ] **Cek Uji Cepat (Linter):**
    Memastikan tidak ada berkas PHP baru yang memiliki syntax error akibat perubahan namespace.

---

## 📅 STATUS EKSEKUSI
Rencana ini dirancang agar dapat selesai dengan cepat dalam satu siklus kerja. Siap dieksekusi begitu Anda memberikan konfirmasi persetujuan.
