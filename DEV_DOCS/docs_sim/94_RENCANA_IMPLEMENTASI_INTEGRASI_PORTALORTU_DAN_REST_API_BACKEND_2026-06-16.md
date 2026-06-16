# Rencana Implementasi: Integrasi Portalortu & Laravel Backend REST API

Rencana ini merinci penambahan skema database, model Eloquent, dan endpoint REST API pada **simt-backend** agar sepenuhnya mendukung kebutuhan data dan fungsionalitas dari **simt-portalortu** (Next.js parent/student portal).

## User Review Required

> [!IMPORTANT]
> 1. **Integritas Multi-Tenant**: Semua model baru (`Schedule`, `StudentViolation`, `StudentAchievement`, `TahfizRecord`, `GradeDetail`) akan menggunakan trait `BelongsToTenant` dan menyertakan field `tenant_id` untuk memastikan isolasi tenant yang aman dan mencegah kebocoran data.
> 2. **Keamanan Autentikasi**: Endpoint autentikasi orang tua (`POST /api/v1/auth/parent-login`) akan menggunakan email dan password untuk membuat token Sanctum (menggunakan akun `User` dengan role `wali`), berbeda dengan mode MVP Next.js yang hanya menggunakan email saja tanpa password.
> 3. **Polimorfisme Token Siswa**: Model `Student` akan dikonfigurasi untuk mendukung `Laravel\Sanctum\HasApiTokens` agar dapat mengeluarkan token API secara mandiri bagi siswa ketika login via NIS & password (`POST /api/v1/auth/student-login`).

## Proposed Changes

---

### [Database & Models]

#### [NEW] [2026_06_16_000002_create_portal_ortu_tables.php](file:///d:/laragon/www/simt-backend/database/migrations/2026_06_16_000002_create_portal_ortu_tables.php)
Migration tunggal untuk menambahkan tabel-tabel baru dan memperluas kolom tabel `students`:
*   **schedules**: `tenant_id`, `class_id`, `subject_id`, `day_of_week` (1-7), `start_period`, `end_period`, `teacher_id` (nullable).
*   **student_violations**: `tenant_id`, `student_id`, `date`, `category` (ringan/sedang/berat), `description`, `points`, `action` (nullable), `recorded_by` (nullable).
*   **student_achievements**: `tenant_id`, `student_id`, `date`, `title`, `category`, `level`, `ranking` (nullable), `description` (nullable), `certificate` (nullable).
*   **tahfiz_records**: `tenant_id`, `student_id`, `date`, `surah`, `ayah_start`, `ayah_end`, `type` (ziyadah/murajaah), `score`, `fluency` (nullable), `note` (nullable), `recorded_by` (nullable).
*   **grade_details**: `tenant_id`, `student_id`, `subject_id`, `category` (TUGAS/HARIAN/UTS/UAS/AKHIR), `title`, `score`, `weight`, `date` (nullable), `note` (nullable).
*   **students (Alter)**: Tambah kolom `photo`, `father_name`, `father_phone`, `mother_name`, `mother_phone`, `parent_email`, `student_password` (nullable).

#### [NEW] [Schedule.php](file:///d:/laragon/www/simt-backend/app/Models/Schedule.php)
Model untuk tabel `schedules` menggunakan trait `BelongsToTenant`. Menghubungkan relasi ke `Tenant`, `SchoolClass`, `Subject`, dan `User` (teacher).

#### [NEW] [StudentViolation.php](file:///d:/laragon/www/simt-backend/app/Models/StudentViolation.php)
Model untuk tabel `student_violations` menggunakan trait `BelongsToTenant`. Menghubungkan relasi ke `Student` dan `User` (recorder).

#### [NEW] [StudentAchievement.php](file:///d:/laragon/www/simt-backend/app/Models/StudentAchievement.php)
Model untuk tabel `student_achievements` menggunakan trait `BelongsToTenant`. Menghubungkan relasi ke `Student`.

#### [NEW] [TahfizRecord.php](file:///d:/laragon/www/simt-backend/app/Models/TahfizRecord.php)
Model untuk tabel `tahfiz_records` menggunakan trait `BelongsToTenant`. Menghubungkan relasi ke `Student` dan `User` (recorder).

#### [NEW] [GradeDetail.php](file:///d:/laragon/www/simt-backend/app/Models/GradeDetail.php)
Model untuk tabel `grade_details` menggunakan trait `BelongsToTenant`. Menghubungkan relasi ke `Student` dan `Subject`.

#### [MODIFY] [Student.php](file:///d:/laragon/www/simt-backend/app/Models/Student.php)
*   Menambahkan kolom baru ke dalam properti `$fillable`: `'photo'`, `'father_name'`, `'father_phone'`, `'mother_name'`, `'mother_phone'`, `'parent_email'`, `'student_password'`.
*   Menambahkan trait `Laravel\Sanctum\HasApiTokens` agar model `Student` dapat menerbitkan Sanctum tokens untuk autentikasi murid.
*   Menambahkan relasi Eloquent:
    *   `schedules()` (melalui classroom/class_student)
    *   `violations()` -> `HasMany(StudentViolation)`
    *   `achievements()` -> `HasMany(StudentAchievement)`
    *   `tahfizRecords()` -> `HasMany(TahfizRecord)`
    *   `gradeDetails()` -> `HasMany(GradeDetail)`

---

### [REST API Endpoints]

#### [NEW] [PortalOrtuApiController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/PortalOrtuApiController.php)
Controller khusus untuk menyajikan data terpadu untuk Portal Ortu:
*   `studentLogin(Request $request)`: Validasi NIS & `student_password`. Jika cocok, keluarkan Sanctum token `student->createToken()` dan kembalikan profil siswa.
*   `parentLogin(Request $request)`: Validasi email & password wali murid (tabel `users`). Jika cocok, keluarkan token dan kembalikan list anak/murid (`guardianStudents`).
*   `dashboard(Request $request, Student $student)`: Kembalikan JSON payload format terpadu untuk Parent Portal Dashboard (profil siswa, ringkasan presensi pintar bulanan, ringkasan nilai, status tagihan keuangan/SPP terbaru, dan pengumuman tenant).
*   `studentDashboard(Request $request, Student $student)`: Kembalikan JSON payload terpadu untuk Student Portal Dashboard (sama seperti parent dashboard + schedules mingguan, violations log, achievements log, dan tahfiz progress records).
*   `gradeDetails(Request $request, Student $student, Subject $subject)`: Kembalikan daftar detail nilai (tugas, harian, uts, uas, akhir) dan rata-ratanya untuk subjek bersangkutan.

#### [MODIFY] [api.php](file:///d:/laragon/www/simt-backend/routes/api.php)
Pendaftaran rute API baru (public & authenticated) di dalam `routes/api.php` or di routes Core:
*   `POST /api/v1/auth/parent-login`
*   `POST /api/v1/auth/student-login`
*   `GET /api/v1/portal/students/{student}/dashboard` (auth:sanctum, tenant-aware, ownership-protected)
*   `GET /api/v1/portal/students/{student}/student-dashboard` (auth:sanctum, tenant-aware, ownership-protected)
*   `GET /api/v1/portal/students/{student}/subjects/{subject}/grade-details` (auth:sanctum, tenant-aware, ownership-protected)

---

### [Seeder & Testing]

#### [MODIFY] [PitchingDemoSeeder.php](file:///d:/laragon/www/simt-backend/database/seeders/PitchingDemoSeeder.php)
Modifikasi seeder demo untuk mengisi data contoh baru ke tabel `schedules`, `student_violations`, `student_achievements`, `tahfiz_records`, `grade_details` serta mengisi field-field orang tua & password siswa pada tabel `students`.

---

### [Verification Plan]

#### Automated Tests
Akan dibuat file test baru **[PortalOrtuApiTest.php](file:///d:/laragon/www/simt-backend/tests/Feature/PortalOrtuApiTest.php)** untuk menguji:
1. Validasi Autentikasi Student (NIS + Password) & Parent (Email + Password).
2. Otorisasi kepemilikan data (mencegah IDOR): memastikan wali murid tidak bisa mengakses dashboard siswa lain.
3. Struktur respon JSON pada dashboard & grade-details agar 100% kompatibel dengan portal Next.js.
4. Isolasi tenant (kebocoran data antar tenant tidak terjadi).

Jalankan test suite menggunakan:
```powershell
php83 artisan test --filter=PortalOrtuApiTest
```

#### Manual Verification
*   Gunakan HTTP Client / Postman / Curl untuk menguji respon endpoint API baru dari server lokal `localhost:8000`.
