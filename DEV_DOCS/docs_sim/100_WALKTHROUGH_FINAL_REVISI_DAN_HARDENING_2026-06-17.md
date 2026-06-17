# 📊 LAPORAN PENYELESAIAN: HARDENING REST API PORTALORTU, SINKRONISASI BLIND INDEX, DAN MONITORING FAILED QUEUE JOBS

**Tanggal:** 17 Juni 2026  
**Waktu:** 07:35 WIB (Local Time)  
**Status:** Completed & Verified  
**Nomor Dokumen:** 100_WALKTHROUGH_FINAL_REVISI_DAN_HARDENING_2026-06-17  

---

## 1. Pendahuluan
Dokumen ini merinci aktivitas penyelesaian masalah (*walkthrough*) dan pengerasan sistem (*hardening*) terhadap REST API **simt-backend** untuk menjamin keamanan optimal, kepatuhan regulasi PDP (Perlindungan Data Pribadi UU No.27/2022), fleksibilitas autentikasi portal orang tua/siswa, serta kemudahan pemantauan antrean gagal (*failed queue jobs*) bagi Superadmin.

---

## 2. Rincian Pekerjaan & Hardening Teknis

### 2.1 Sinkronisasi & Migrasi Blind Index `nisn_bindex` (Option A)
* **Latar Belakang:** Sebelumnya terjadi ketidakcocokan antara controller dan skema basis data setelah adanya rollback enkripsi PII kompleks. 
* **Tindakan:** 
  1. Menerapkan migrasi terarah di [2026_06_16_150805_add_nisn_bindex_to_students_table.php](file:///d:/laragon/www/simt-backend/database/migrations/2026_06_16_150805_add_nisn_bindex_to_students_table.php) untuk menambahkan kolom `nisn_bindex` di database lokal.
  2. Menyediakan hook otomatis pada model [Student.php](file:///d:/laragon/www/simt-backend/app/Models/Student.php) di method `booted()` guna mengamankan lookup `nisn` menggunakan hashing HMAC SHA256 saat model disimpan.
  3. Menjalankan perintah pemeliharaan `php artisan students:update-nisn-bindex` via [UpdateNisnBindex.php](file:///d:/laragon/www/simt-backend/app/Console/Commands/UpdateNisnBindex.php) untuk melengkapi data blind index siswa yang sudah ada.

### 2.2 Pengerasan Autentikasi Siswa (Paksa Bcrypt)
* **Latar Belakang:** Properti `student_password` sebelumnya mendukung pencocokan teks biasa (*plain-text*) sebagai fallback kompatibilitas MVP yang berisiko tinggi.
* **Tindakan:**
  1. Menambahkan mutator `setStudentPasswordAttribute` pada [Student.php](file:///d:/laragon/www/simt-backend/app/Models/Student.php) untuk memastikan setiap password siswa yang dimasukkan langsung disandikan secara aman menggunakan Bcrypt.
  2. Menghapus logika pencocokan plain-text fallback di method `studentLogin` pada [PortalOrtuApiController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/PortalOrtuApiController.php) dan menggantinya secara ketat dengan `Hash::check()`.

### 2.3 Autentikasi Fleksibel Wali Murid (Email atau No. HP/WA)
* **Latar Belakang:** Logika login wali murid pada REST API sebelumnya hanya membatasi pencarian lewat alamat email saja, sedangkan dokumentasi PRD mengamanatkan penggunaan No. HP/WA.
* **Tindakan:**
  1. Memodifikasi method `parentLogin` pada [PortalOrtuApiController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/PortalOrtuApiController.php) agar field input login secara fleksibel mencocokkan kolom `email` **maupun** `phone` pada tabel `users`.
  2. Menambahkan skenario pengujian `test_parent_can_login_with_phone_number` pada [PortalOrtuApiTest.php](file:///d:/laragon/www/simt-backend/tests/Feature/PortalOrtuApiTest.php) untuk menguji keabsahan alur autentikasi ini.

### 2.4 Proteksi Serangan Brute Force (Rate Limiting)
* **Tindakan:** 
  1. Menerapkan pembatasan ketat `throttle:5,1` (maksimum 5 kali percobaan per menit) di [api.php](file:///d:/laragon/www/simt-backend/routes/api.php) untuk endpoint autentikasi publik `/v1/auth/login`, `/v1/auth/parent-login`, dan `/v1/auth/student-login`.
  2. Memasang rate limit `throttle:60,1` (maksimum 60 kali pemanggilan per menit) pada rute API portal terautentikasi guna mencegah tindakan scraping massal atau eksploitasi lainnya.

### 2.5 Pencegahan Kebocoran Data Multi-Tenant (Tenant-Aware Announcements)
* **Tindakan:** Menambahkan klausa penapis eksplisit `where('tenant_id', $student->tenant_id)` pada kueri pengumuman di [PortalOrtuApiController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/PortalOrtuApiController.php). Hal ini memastikan sekolah tidak akan pernah menampilkan pengumuman milik tenant sekolah lain (*Defense in Depth*).

### 2.6 Dashboard Monitoring Failed Queue Jobs (Superadmin)
* **Tindakan:**
  1. Menambahkan rute `/admin/failed-jobs` (serta aksi POST untuk retry dan DELETE untuk hapus) di [web.php](file:///d:/laragon/www/simt-backend/routes/web.php).
  2. Menambahkan penanganan kueri failed jobs menggunakan facade `DB` dan eksekusi command Artisan `queue:retry` / `queue:forget` pada [SuperAdminController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/SuperAdminController.php).
  3. Membangun halaman antarmuka [failed_jobs.blade.php](file:///d:/laragon/www/simt-backend/Modules/Core/resources/views/super/failed_jobs.blade.php) yang memuat daftar kegagalan antrean, nama kelas pekerjaan, waktu kegagalan, dan detil pengecualian (*exception log*) lengkap dengan kontrol admin terpadu.
  4. Menautkan link monitoring di panel navigasi atas [dashboard.blade.php](file:///d:/laragon/www/simt-backend/Modules/Core/resources/views/super/dashboard.blade.php) Superadmin.

---

## 3. Daftar File yang Dibuat / Diperbarui

| File Path | Modul / Sektor | Penjelasan Perubahan |
| :--- | :--- | :--- |
| **[Student.php](file:///d:/laragon/www/simt-backend/app/Models/Student.php)** | Models (Core) | Penambahan `setStudentPasswordAttribute()` mutator untuk Bcrypt otomatis. |
| **[PortalOrtuApiController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/PortalOrtuApiController.php)** | Controllers (Core) | Enforce hash check siswa, dynamic email/phone wali login, dan filter tenant-aware pengumuman. |
| **[api.php](file:///d:/laragon/www/simt-backend/routes/api.php)** | Routes (API) | Penambahan middleware `throttle` (rate-limiting) untuk auth (5/mnt) dan portal (60/mnt). |
| **[web.php](file:///d:/laragon/www/simt-backend/routes/web.php)** | Routes (Web) | Penambahan rute monitoring, retry, dan delete failed queue jobs untuk Superadmin. |
| **[SuperAdminController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/SuperAdminController.php)** | Controllers (Superadmin) | Aksi `failedJobs()`, `retryFailedJob()`, dan `deleteFailedJob()`. |
| **[failed_jobs.blade.php](file:///d:/laragon/www/simt-backend/Modules/Core/resources/views/super/failed_jobs.blade.php)** | Views (Superadmin) | **[NEW]** Halaman tabel log kegagalan antrean kerja beserta kontrol pemulihan. |
| **[dashboard.blade.php](file:///d:/laragon/www/simt-backend/Modules/Core/resources/views/super/dashboard.blade.php)** | Views (Superadmin) | Penambahan header link cepat ke monitoring failed jobs dan audit logs. |
| **[PortalOrtuApiTest.php](file:///d:/laragon/www/simt-backend/tests/Feature/PortalOrtuApiTest.php)** | Tests (Feature) | Penambahan test case autentikasi wali menggunakan nomor handphone. |
| **[SuperAdminTest.php](file:///d:/laragon/www/simt-backend/tests/Feature/SuperAdminTest.php)** | Tests (Feature) | Penambahan test case otorisasi akses menu monitoring failed jobs bagi Superadmin. |

---

## 4. Validasi & Unit Testing

Seluruh test suite backend Laravel telah diuji ulang secara lokal menggunakan PHP 8.3 CLI:
```powershell
php83 artisan test
```

### Hasil Uji Terakhir:
* **Total Tests:** **70 Passed** (Bertambah dari 67 pasca penambahan test case baru)
* **Total Assertions:** **257 Assertions**
* **Durasi Eksekusi:** ~91.80 detik
* **Status Build:** 🟢 **Success (Green)**

Seluruh fitur baru terbukti berjalan lancar tanpa efek samping ataupun kebocoran isolasi multi-tenant pada database.
