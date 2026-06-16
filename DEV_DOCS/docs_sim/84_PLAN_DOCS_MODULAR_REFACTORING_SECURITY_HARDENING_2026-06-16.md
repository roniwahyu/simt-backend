# 📊 RENCANA IMPLEMENTASI: REFACTORING VIEW MODULAR, PENGASAHAN KEAMANAN, REUSABLE PARTIALS, DAN DESAIN PREMIUM

**Tanggal:** 16 Juni 2026  
**Status:** Draft (Awaiting Approval)  
**Prioritas:** High  
**Nomor Dokumen:** 84_PLAN_DOCS_MODULAR_REFACTORING_SECURITY_HARDENING_2026-06-16  

---

## 1. Ringkasan Tujuan
Dokumen ini merumuskan langkah-langkah strategis untuk menata ulang arsitektur tampilan (*Blade Views*) modul agar terisolasi secara penuh (*Fully Modular*), mengunci rute-rute penting dengan proteksi *Role-Based Access Control* (RBAC) via middleware Spatie Permission (*Security Hardening*), mengekstrak elemen antarmuka berulang (*Reusable Partials*), serta mendesain ulang antarmuka menjadi sangat modern dan dinamis sesuai standar industri (*Modern UI/UX*).

---

## 2. Rincian Perubahan yang Diusulkan

### 2.1 Reorganisasi Tampilan Modul (Fully Modular)
Untuk memisahkan tanggung jawab (separation of concerns), seluruh berkas tampilan global di `resources/views/admin/` akan dipindahkan ke masing-masing modul terkait. Controller akan diperbarui untuk memanggil namespace view modul (misalnya `student::index` alih-alih `admin.student.index`).

| Tampilan Lama (Global) | Tampilan Baru (Modular) | Controller Terkait |
|---|---|---|
| `resources/views/admin/dashboard.blade.php` | `Modules/Core/resources/views/dashboard.blade.php` | `DashboardController` (`core::dashboard`) |
| `resources/views/admin/super/*` | `Modules/Core/resources/views/super/*` | `SuperAdminController` (`core::super.*`) |
| `resources/views/admin/student/*` | `Modules/Student/resources/views/*` | `StudentController` (`student::*`) |
| `resources/views/admin/attendance/*` | `Modules/Attendance/resources/views/*` | `AttendanceController` (`attendance::*`) |
| `resources/views/pdf/receipt.blade.php` | `Modules/Finance/resources/views/receipt.blade.php` | `FinanceController` (`finance::receipt`) |

### 2.2 Pengerasan Keamanan (Security Hardening)
Kami mendeteksi rute-rute web dan API pada beberapa modul yang belum dikunci secara ketat menggunakan middleware Spatie Permission di tingkat rute. Kami mengusulkan proteksi tambahan berikut:

* **Modul Student:**
  - `/students` -> `permission:view_students`
  - `/students/create`, `/students` (POST) -> `permission:create_students`
  - `/students/{student}/edit`, `/students/{student}` (PUT) -> `permission:edit_students`
  - `/students/{student}` (DELETE) -> `permission:delete_students`
  - `/students/import/*` -> `permission:import_students`
* **Modul Attendance:**
  - `/attendance`, `/attendance/class/*` -> `permission:view_attendance`
* **Modul Finance:**
  - `/finance/bills` -> `permission:view_bills`
  - `/finance/bills/generate` -> `permission:create_bills`
  - `/bills/{bill}/payment` -> `permission:record_payment`
  - `/payments/{payment}/receipt` -> `permission:print_receipt`
  - `/finance/reminders` -> `permission:send_reminders`
  - `/finance/bills/export` -> `permission:view_bills`
* **Modul Notification (WhatsApp):**
  - Seluruh rute `/admin/notification/*` -> `permission:wa.connect`
* **Modul Akademik (Baru):**
  - Mendaftarkan permission baru: `view_akademik`, `manage_akademik`, `manage_grades`.
  - Memetakan permission tersebut ke dalam `RolePermissionSeeder` dan `TenantRoleService::ROLE_MATRIX` untuk role `admin_sekolah`, `kepala_madrasah`, `tu`, dan `guru`.
  - Mengunci seluruh rute akademik dengan middleware permission yang baru didefinisikan.

### 2.3 Pembentukan Partials & Reusable Components
Untuk meminimalisir duplikasi kode HTML pada komponen alert:
1. **Membuat berkas baru** `Modules/Core/resources/views/partials/alerts.blade.php`.
2. Berkas ini bertugas menangani rendering flash message `session('success')` dan `session('error')` dengan gaya premium (soft background, custom borders, modern alert icons, and dismiss button).
3. **Merefaktor** `resources/views/layouts/app.blade.php` untuk memanggil `@include('core::partials.alerts')`.

### 2.4 Peningkatan Desain UI/UX (Premium Look & Feel)
Halaman Kesiswaan, Presensi, dan Keuangan akan dipoles menggunakan Tailwind CSS modern:
* **Student/Kesiswaan:** Menampilkan ringkasan kartu statistik di bagian atas (Total Siswa, Laki-laki, Perempuan, Status Aktif) dengan ikon halus, tabel modern dengan efek hover, badge status dengan warna harmonis (Emerald untuk aktif, Gray untuk nonaktif), dan pembersih form filter.
* **Attendance/Presensi:** Menata ulang grid presensi siswa dengan hover effects yang responsif, visual badge status presensi yang menonjol dan kontras, serta pesan konfirmasi visual saat data presensi berubah sebelum disimpan.
* **Finance/Keuangan:** Menampilkan filter tagihan dengan rapi, status lunas/sebagian/belum bayar menggunakan badge berwarna premium (Lunas = Emerald, Sebagian = Amber, Belum Lunas = Rose), mempercantik desain modal rekam pembayaran, dan merapikan formulir pembuatan tagihan massal.

### 2.5 Penghapusan Modul Inaktif (Clean Codebase)
* **Direktori `Modules/Pegawai` & `Modules/Ppdb`:** Menghapus kedua direktori skeleton ini untuk menjaga performa autoloader dan kebersihan struktur folder project.

---

## 3. Rencana Verifikasi

### 3.1 Otomatisasi (Automated Testing)
1. Menjalankan migrasi fresh beserta seeding seluruh permission baru untuk memastikan database skema berjalan tanpa kendala:
   ```powershell
   php83 artisan migrate:fresh --seed
   ```
2. Menjalankan rangkaian uji otomatis lengkap untuk memastikan tidak ada regresi:
   ```powershell
   php83 artisan test
   ```
3. Menambahkan test case otorisasi untuk memverifikasi bahwa pengguna tanpa permission akademik atau keuangan diblokir dengan status `403 Forbidden`.

### 3.2 Manual (Manual Verification)
1. Login dengan berbagai role (`guru`, `bendahara`, `tu`, `kepala_madrasah`) dan verifikasi visibilitas menu di sidebar serta izin akses URL secara langsung.
2. Memeriksa konsistensi visual layout, keindahan komponen alerts, modal rekam pembayaran, serta hasil cetak PDF kwitansi & rapor.
