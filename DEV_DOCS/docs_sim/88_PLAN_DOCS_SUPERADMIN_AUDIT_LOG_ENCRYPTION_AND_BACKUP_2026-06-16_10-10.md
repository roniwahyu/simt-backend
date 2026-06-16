# 📊 RENCANA IMPLEMENTASI: SUPERADMIN DASHBOARD EXPANSION, AUDIT LOG UI, ENKRIPSI PII DENGAN BLIND INDEX, DAN SETUP SPATIE BACKUP

**Tanggal:** 16 Juni 2026  
**Waktu:** 10:10 WIB (Local Time)  
**Status:** Draft (Awaiting Approval)  
**Prioritas:** High  
**Nomor Dokumen:** 88_PLAN_DOCS_SUPERADMIN_AUDIT_LOG_ENCRYPTION_AND_BACKUP_2026-06-16_10-10  

---

## 1. Ringkasan Tujuan
Dokumen ini menyajikan rencana pengembangan dan implementasi untuk empat pilar utama:
1. **Superadmin (SA-01 sd SA-04):** Memperluas metrik dashboard, membuat Laporan Lintas Tenant (Usage Report), serta membangun uji pengujian Superadmin.
2. **Audit Log UI (AL-01 sd AL-03):** Membuat model Eloquent `AuditLog`, serta membangun halaman log aktivitas lengkap dengan filter tenant & filter user baik untuk Superadmin maupun Tenant Admin.
3. **Enkripsi PII Tambahan (EN-01, EN-02):** Mengenkripsi field sensitif tambahan pada `Student` (`nisn`, `birth_date`, `birth_place`, `gender`). Khusus `nisn`, kami merancang mekanisme pencarian dan validasi unik berbasis **Blind Index** (`nisn_bindex`) untuk kepatuhan UU PDP tanpa mengorbankan performa kueri.
4. **Setup Spatie Backup (BK-01 sd BK-03):** Mengintegrasikan `spatie/laravel-backup` untuk backup harian terenkripsi (zip password) yang siap diunggah ke cloud storage, lengkap dengan notifikasi scheduler.

---

## 2. Analisis Kesenjangan & Kesiapan Kode

| ID | Fitur / Task | Status di Kode | Rencana Tindakan | Kesiapan |
| :--- | :--- | :--- | :--- | :---: |
| **SA-01** | Dashboard Superadmin | **Parsial** | Tambahkan statistik dinamis per tenant (jumlah siswa, kelas, modul aktif) di dashboard Superadmin. | 50% |
| **SA-02** | Toggle Modul Aktif per Tenant | **Sudah Ada** | UI & backend toggle modul sudah siap di `SuperAdminController`. | 100% |
| **SA-03** | Laporan Penggunaan Lintas Tenant | **Belum Ada** | Tambahkan tabel komparasi detail resource antar tenant di dashboard Superadmin. | 0% |
| **SA-04** | UI & Testing Superadmin | **Belum Ada** | Buat test suite `Tests\Feature\SuperAdminTest` untuk memverifikasi fungsionalitas panel Superadmin. | 0% |
| **AL-01** | Audit Log Backend | **Parsial** | Buat model Eloquent `App\Models\AuditLog` untuk mempermudah pemanggilan data log. | 70% |
| **AL-02** | Tampilan Log + Filter per Tenant | **Belum Ada** | Buat rute `/admin/audit-logs` (Superadmin) dan `/audit-logs` (Tenant Admin) beserta visualisasi tabel berfilter. | 0% |
| **AL-03** | Testing Audit Log | **Belum Ada** | Buat test suite `Tests\Feature\AuditLogTest` untuk validasi filter dan gating akses log. | 0% |
| **EN-01** | Enkripsi Data Sensitif (NISN, dll) | **Belum Eksplisit** | Enkripsi `nisn`, `birth_date`, `birth_place`, dan `gender` di model `Student`. Tambahkan kolom `nisn_bindex` (SHA256) untuk exact match lookup dan unique check. | 20% |
| **EN-02** | Testing Enkripsi | **Belum Ada** | Tulis test case pembuktian ciphertext at-rest di database dan keberhasilan pencarian berbasis blind index. | 0% |
| **BK-01** | Backup Harian (Spatie) | **Belum Ada** | Pasang package `spatie/laravel-backup` via composer dan konfigurasi harian scheduler pukul 02:00 WIB. | 0% |
| **BK-02** | Cloud Backup + Enkripsi | **Belum Ada** | Aktifkan enkripsi zip dan konfigurasikan target cloud disk. | 0% |
| **BK-03** | Monitoring & Notifikasi Backup | **Belum Ada** | Tambahkan notifikasi event backup sukses/gagal via email/logs. | 0% |

---

## 3. Detail Rencana Implementasi

### 3.1 Superadmin & Laporan Lintas Tenant (SA-01, SA-03, SA-04)
Kami akan memperluas `SuperAdminController` agar tidak hanya memuat jumlah pengguna, melainkan menghitung total siswa, kelas, dan modul aktif per tenant:
* **Statistik Detail:**
  ```php
  // Modules/Core/app/Http/Controllers/SuperAdminController.php
  $tenants = Tenant::withCount(['users', 'students', 'classes', 'modules' => function($q) {
      $q->where('active', true);
  }])->latest()->paginate(20);
  ```
* **Laporan Penggunaan Lintas Tenant:** Tampilkan tabel ringkasan komparasi resource di tab dashboard utama.
* **Testing:** Tulis `Tests\Feature\SuperAdminTest.php` untuk memvalidasi akses peran `superadmin`, pembuatan tenant baru, dan perubahan status modul.

### 3.2 Audit Log UI (AL-01, AL-02, AL-03)
* **Model Eloquent `AuditLog`:**
  ```php
  // app/Models/AuditLog.php
  namespace App\Models;
  use Illuminate\Database\Eloquent\Model;
  class AuditLog extends Model {
      protected $fillable = ['tenant_id', 'user_id', 'event', 'auditable_type', 'auditable_id', 'old_values', 'new_values', 'ip_address', 'user_agent'];
      protected $casts = ['old_values' => 'array', 'new_values' => 'array'];
      public function tenant() { return $this->belongsTo(Tenant::class); }
      public function user() { return $this->belongsTo(User::class); }
  }
  ```
* **Rute Baru:**
  * Superadmin: `Route::get('/admin/audit-logs', [SuperAdminController::class, 'auditLogs'])->name('super.audit-logs');`
  * Tenant Admin: `Route::get('/audit-logs', [DashboardController::class, 'auditLogs'])->name('audit-logs');`
* **Filter log:** Filter berdasarkan tenant (hanya Superadmin), user, event type (created, updated, deleted, login), model type, dan tanggal.

### 3.3 Enkripsi PII Tambahan & Blind Index (EN-01, EN-02)
* **Migrasi Database (`database/migrations/2026_06_16_000002_encrypt_student_sensitive_fields.php`):**
  ```php
  Schema::table('students', function (Blueprint $table) {
      $table->dropUnique('students_tenant_nisn_unique');
      $table->text('nisn')->nullable()->change();
      $table->text('birth_date')->nullable()->change();
      $table->text('birth_place')->nullable()->change();
      $table->text('gender')->nullable()->change();
      $table->string('nisn_bindex', 64)->nullable()->after('nisn');
      $table->unique(['tenant_id', 'nisn_bindex'], 'students_tenant_nisn_bindex_unique');
  });
  ```
* **Model Student (`app/Models/Student.php`):**
  ```php
  protected $casts = [
      'nisn' => 'encrypted',
      'birth_date' => 'encrypted:date',
      'birth_place' => 'encrypted',
      'gender' => 'encrypted',
      'address' => 'encrypted',
  ];
  
  protected static function booted()
  {
      static::saving(function ($student) {
          if ($student->isDirty('nisn')) {
              $student->nisn_bindex = $student->nisn 
                  ? hash_hmac('sha256', $student->nisn, config('app.key')) 
                  : null;
          }
      });
  }
  ```
* **Modifikasi Controller Pencarian:**
  Ganti query `$query->orWhere('nisn', 'like', ...)` dengan exact match:
  ```php
  $hashedSearch = hash_hmac('sha256', $search, config('app.key'));
  $query->orWhere('nisn_bindex', $hashedSearch);
  ```
* **Modifikasi Validasi Unik (StudentController):**
  Sebelum validasi, jika `nisn` diisi, lakukan hash hmac dan merge ke request sebagai `nisn_bindex`, lalu lakukan validasi `Rule::unique('students', 'nisn_bindex')->where('tenant_id', $tenantId)->ignore($student->id)`.

### 3.4 Setup Spatie Backup (BK-01, BK-02, BK-03)
* **Pemasangan Package:**
  ```bash
  composer require spatie/laravel-backup
  ```
* **Konfigurasi (`config/backup.php`):**
  * Backup target database `mysql`.
  * Masukkan zip password encryption di `config/backup.php` (memanfaatkan database encryption key).
  * Daftarkan daily backup scheduler di `routes/console.php`.

---

## 4. Estimasi Kerja (Sprint 5 & 6)

| Task ID | Kegiatan / Fitur | Estimasi (Jam) | Prioritas |
| :--- | :--- | :---: | :---: |
| **SA-01** | Dashboard Superadmin (Statistik usage detail per tenant) | 3 | Tinggi |
| **SA-03** | Laporan Penggunaan Lintas Tenant (Tabel komparasi resource) | 3 | Sedang |
| **SA-04** | UI & Testing Superadmin (Test suite `SuperAdminTest.php`) | 4 | Tinggi |
| **AL-01** | Audit Log Model & Gating (`AuditLog` Model & Trait link) | 2 | Tinggi |
| **AL-02** | Tampilan Log + Filter per Tenant (UI log di Superadmin & Admin) | 5 | Tinggi |
| **AL-03** | Testing Audit Log (Test suite `AuditLogTest.php`) | 3 | Tinggi |
| **EN-01** | Enkripsi Data Sensitif & Blind Index (Migrasi, model casts, search & validation) | 6 | Tinggi |
| **EN-02** | Testing Enkripsi (Test case blind index lookup & raw DB verification) | 3 | Tinggi |
| **BK-01** | Backup Harian (Pasang & daftarkan Spatie Backup harian) | 3 | Sedang |
| **BK-02** | Cloud Backup & Enkripsi (Enkripsi password zip & setup filesystem disk) | 3 | Sedang |
| **BK-03** | Monitoring & Notifikasi Backup (Email/logs channel notifications) | 2 | Rendah |
| | **TOTAL** | **37 Jam** | |

---

## 5. Rencana Verifikasi

### 5.1 Pengujian Otomatis
1. Menjalankan pengujian otomatis untuk memverifikasi fungsionalitas superadmin, filter audit logs, enkripsi at-rest, dan blind index:
   ```powershell
   php83 artisan test
   ```

### 5.2 Pengujian Manual
1. Jalankan migrasi baru: `php83 artisan migrate`.
2. Lakukan tambah/update siswa dan periksa database `students` untuk membuktikan bahwa data `nisn`, `birth_date` dienkripsi dan kolom `nisn_bindex` terisi hash.
3. Jalankan `php83 artisan backup:run` untuk memastikan arsip zip terenkripsi dan terbuat di `storage/app/SIMT-backup/`.
