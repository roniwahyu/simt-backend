# 📊 LAPORAN ANALISIS & RENCANA TINDAK LANJUT: EVALUASI KODE MONOLITIK & PEMBERSIHAN

**Tanggal:** 15 Juni 2026  
**Waktu:** 23:25 WITA (Local Time)  
**Status:** Usulan Rencana Kerja  
**Topik:** Evaluasi File Baru (Config, Models, Middleware, Controllers, & Migrations) & Rencana Aksi Pembersihan

---

## 1. Analisis Berkas & Konflik Teknis Mendalam

Setelah memeriksa kode sumber dari seluruh berkas baru (*untracked*) yang ditambahkan ke repositori, berikut adalah analisis rinci mengenai kecocokan mereka dengan arsitektur **Modular Multi-Tenant (Nwidart)** yang saat ini aktif:

### 1.1 Konfigurasi (`config/`)
*   **`config/simt.php`**  
    *   *Analisis:* File konfigurasi baru ini berisi pengaturan modul monolitik, API Dapodik/EMIS, konfigurasi Payment (Midtrans/Xendit), dan WhatsApp (Baileys gateway port 3003). 
    *   *Masalah:* Terjadi tumpang tindih dengan konfigurasi bawaan modul aktif. Misalnya, integrasi WhatsApp pada `Modules/Notification` menggunakan port `8081` (via `config/app.php`), bukan port `3003`.

### 1.2 Model (`app/Models/`)
*   **`app/Models/Announcement.php`**  
    *   *Analisis:* Model untuk pengumuman. Fitur pengumuman belum ada di skema database modular saat ini, dan merupakan backlog Sprint 5.
*   **`app/Models/Grade.php`**  
    *   *Analisis:* Model untuk nilai siswa. Merujuk ke model `Subject` dan `Classroom` (monolitik).
*   **`app/Models/WhatsappConfig.php`**  
    *   *Analisis:* Menyimpan konfigurasi sesi Baileys per-tenant. Bersifat redundan dengan tabel `wa_notifications` di `Modules/Notification` yang menggunakan singleton `Tenancy` untuk scoping data.

### 1.3 Middleware (`app/Http/Middleware/`)
*   **`app/Http/Middleware/CheckModuleSubscription.php`**  
    *   *Analisis:* Mencoba mengecek hak akses modul via kolom `module_akademik`, `module_keuangan` langsung di tabel `tenants`. Skema database modular menggunakan tabel pivot `tenant_modules` dengan kolom `module_code` untuk membatasi akses modul.
*   **`app/Http/Middleware/SetTenantContext.php` & `TenantScope.php`**  
    *   *Analisis:* Mencoba menyetel global scope untuk model-model tenant secara dinamis dalam runtime request. 
    *   *Masalah:* Sistem modular core sudah memiliki trait `App\Traits\BelongsToTenant` yang mengikat global scope secara native via model boot. Pendekatan middleware dinamis ini redundan dan memicu error karena memanggil kelas model yang tidak ada (`AcademicYear`, `Classroom`).

### 1.4 Controller (`app/Http/Controllers/`)
*   **`StudentController.php` & `AttendanceController.php`**  
    *   *Masalah:* Menggunakan query ke tabel monolitik (`classrooms` dan `academic_years`). `AttendanceController` juga merekam status absensi dalam format teks panjang (`HADIR`, `SAKIT`, `IZIN`, `ALPHA`) dan kolom `time_in` / `recorded_by`, sedangkan skema modular core menggunakan single-character code (`H`, `S`, `I`, `A`) dan kolom `arrival_time` / `marked_by`.
*   **`DashboardController.php`**  
    *   *Masalah:* Mengabaikan controller dashboard modular aktif (`Modules\Core\Http\Controllers\DashboardController`).
*   **`WhatsappController.php`**  
    *   *Masalah:* Mengontrol koneksi Baileys secara langsung pada port 3003, redundan dengan `NotificationController` modular aktif pada port 8081.

### 1.5 Migrasi Database (`database/migrations/`)
*   **Pending Migrations (`0003` s.d. `0011`):**  
    *   *Duplikasi Tabel Core:* Migrasi `0006_create_students_table`, `0007_create_attendances_table`, dan `0009_create_payments_table` akan memicu error fatal saat dideploy karena tabel-tabel tersebut sudah dibuat di Batch 1.
    *   *Perbedaan Nama Tabel:* Migrasi `0003` (`academic_years`) dan `0004` (`classrooms`) bertabrakan dengan tabel master core yang aktif (`school_years` dan `school_classes`).

---

## 2. Matriks Keputusan: Jalankan, Bersihkan, Hapus

Berdasarkan analisis di atas, berikut adalah matriks keputusan untuk setiap berkas:

| No | Berkas / Direktori | Rekomendasi Tindakan | Keterangan & Rencana Kerja |
|:---|:---|:---|:---|
| **1** | `config/simt.php` | **DIBERSIHKAN (Pindahkan)** | Konfigurasi Dapodik, EMIS, & Midtrans dipindahkan ke `config/app.php` atau file config modular baru. Konfigurasi WhatsApp monolitik dihapus karena sudah dicakup oleh `Modules/Notification`. |
| **2** | `app/Http/Middleware/CheckModuleSubscription.php` | **DIHAPUS** | Pengujian subscription tenant sudah ditangani secara modular oleh middleware group `module.active:ModuleName`. |
| **3** | `app/Http/Middleware/SetTenantContext.php` | **DIHAPUS** | Pengaturan konteks tenant dilakukan oleh middleware `IdentifyTenant` & `SetTenantFromUser` core. |
| **4** | `app/Http/Middleware/TenantScope.php` | **DIHAPUS** | Scoping tenant sudah ditangani secara native melalui trait `BelongsToTenant`. |
| **5** | `app/Models/Announcement.php` | **DIBERSIHKAN (Pindahkan)** | Pindahkan model ini ke dalam `Modules/Notification/app/Models/Announcement.php` untuk mendukung fitur pengumuman. |
| **6** | `app/Models/Grade.php` | **DIBERSIHKAN (Pindahkan)** | Pindahkan model ini ke modul akademik baru atau adaptasikan ke `Modules/Student` jika diperlukan, disesuaikan dengan skema `school_classes`. |
| **7** | `app/Models/WhatsappConfig.php` | **DIHAPUS** | Redundan. Pengelolaan sesi WhatsApp dilakukan secara modular di `Modules/Notification`. |
| **8** | `app/Http/Controllers/DashboardController.php` | **DIHAPUS** | Gunakan `Modules\Core\Http\Controllers\DashboardController` yang sudah terpetakan pada rute `/dashboard`. |
| **9** | `app/Http/Controllers/StudentController.php` | **DIHAPUS** | Gunakan `Modules\Student\Http\Controllers\StudentController` yang sudah mendukung impor/ekspor data berbasis multi-tenant. |
| **10** | `app/Http/Controllers/AttendanceController.php` | **DIHAPUS** | Gunakan `Modules\Attendance\Http\Controllers\AttendanceController`. |
| **11** | `app/Http/Controllers/PaymentController.php` | **DIHAPUS** | Gunakan `Modules\Finance\Http\Controllers\FinanceController`. |
| **12** | `app/Http/Controllers/WhatsappController.php` | **DIHAPUS** | Gunakan `Modules\Notification\Http\Controllers\NotificationController`. |
| **13** | `app/Http/Controllers/AnnouncementController.php` | **DIBERSIHKAN (Pindahkan)** | Pindahkan ke `Modules/Notification/app/Http/Controllers/AnnouncementController.php`. |
| **14** | `app/Http/Controllers/GradeController.php` | **DIBERSIHKAN (Pindahkan)** | Pindahkan ke modul nilai atau akademik baru, sesuaikan kueri modelnya agar merujuk ke `SchoolClass`. |
| **15** | `database/migrations/0003_...academic_years` | **DIHAPUS** | Gunakan tabel `school_years` yang sudah ada. |
| **16** | `database/migrations/0004_...classrooms` | **DIHAPUS** | Gunakan tabel `school_classes` yang sudah ada. |
| **17** | `database/migrations/0005_...subjects` | **DIJALANKAN (Sesuaikan)** | Ubah foreign key `classroom_id` menjadi `school_class_id` yang merujuk ke `school_classes`. |
| **18** | `database/migrations/0006_...students` | **DIHAPUS** | Tabel `students` sudah dibuat di Batch 1. Kolom tambahan (seperti nama ayah/ibu, dll) dapat ditambahkan melalui migrasi alter tabel jika diperlukan. |
| **19** | `database/migrations/0007_...attendances` | **DIHAPUS** | Tabel `attendances` sudah dibuat di Batch 1. |
| **20** | `database/migrations/0008_...grades` | **DIJALANKAN (Sesuaikan)** | Sesuaikan foreign key agar merujuk ke tabel core (`students`, `school_classes`, `subjects`). |
| **21** | `database/migrations/0009_...payments` | **DIHAPUS** | Tabel `payments` (dan tagihan `bills`) sudah dibuat di Batch 1. |
| **22** | `database/migrations/0010_...announcements` | **DIJALANKAN** | Jalankan migrasi ini untuk mendukung tabel pengumuman baru. |
| **23** | `database/migrations/0011_...whatsapp_configs` | **DIHAPUS** | Gunakan tabel notifikasi modular `wa_notifications` yang sudah ada di Batch 1. |

---

## 3. Rencana Langkah Kerja Eksekusi (Next Steps)

Untuk menerapkan rencana di atas tanpa merusak sistem yang sedang berjalan, langkah-langkah berikut akan ditempuh setelah mendapatkan persetujuan:

### Langkah 1: Pembersihan Berkas Monolitik Redundan (Delete)
*   Menghapus middleware monolitik yang bertabrakan di `app/Http/Middleware/`.
*   Menghapus model monolitik redundan di `app/Models/` (`WhatsappConfig.php`).
*   Menghapus controller monolitik redundan di `app/Http/Controllers/` (`StudentController`, `AttendanceController`, `DashboardController`, `PaymentController`, `WhatsappController`).
*   Menghapus berkas migrasi duplikat di `database/migrations/` (`0003_`, `0004_`, `0006_`, `0007_`, `0009_`, `0011_`).

### Langkah 2: Pemindahan & Penyelarasan Fitur Baru (Refactor & Clean)
*   Memindahkan model `Announcement.php` ke modul `Notification` (`Modules/Notification/app/Models/Announcement.php`).
*   Memindahkan `AnnouncementController.php` ke modul `Notification`.
*   Membuat modul baru atau menyesuaikan model `Grade.php` & `GradeController.php` agar bekerja dengan skema kelas core (`SchoolClass` dan `SchoolYear`).
*   Menyesuaikan migrasi pending (`0005_create_subjects_table.php` dan `0008_create_grades_table.php`) dengan mengubah referensi `classroom_id` menjadi `school_class_id`.

### Langkah 3: Eksekusi Migrasi Baru (Run)
*   Menjalankan `php83 artisan migrate` untuk memigrasikan tabel `subjects`, `grades`, dan `announcements` yang telah diselaraskan.
*   Memastikan aplikasi tetap stabil dengan menjalankan kembali pengecekan rute (`php83 artisan route:list`) dan pengujian.
