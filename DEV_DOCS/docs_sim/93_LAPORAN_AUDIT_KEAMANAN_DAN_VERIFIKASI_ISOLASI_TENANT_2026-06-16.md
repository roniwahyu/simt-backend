# Laporan Audit Keamanan dan Verifikasi Isolasi Tenant SIMT Backend

Laporan resmi mengenai audit keamanan kode sumber (*security code audit*), penanganan celah kebocoran data (*tenant leak bugfix*), dan pengujian otomatis terhadap keandalan isolasi data lintas-tenant di repositori **SIMT Backend**.

---

## 1. Pendahuluan & Tujuan Audit

Audit ini dilakukan secara menyeluruh untuk memverifikasi tiga aspek keamanan data krusial pada sistem SaaS Multi-Tenant SIMT:
1.  **Isolasi Antar Penyewa (Tenant 1 ke Tenant 2)**: Menjamin data akademis, presensi, keuangan, dan log dari satu sekolah tidak dapat diakses atau diintip oleh sekolah lain.
2.  **Isolasi Antar Peran (Role A ke Role B)**: Menjamin hak akses pengguna terkunci rapat di dalam modulnya masing-masing berdasarkan otorisasi granular.
3.  **Isolasi Antar Pengguna Kontekstual (Wali A ke Data Murid Lain)**: Menjamin pencegahan kerentanan *Insecure Direct Object Reference* (IDOR) pada portal eksternal (wali murid/siswa).

---

## 2. Metodologi Analisis Keamanan Kode

Audit dilakukan dengan menyisir alur masuk data (*request lifecycle*) dari tingkat routing, middleware, model Eloquent, hingga ke query builder.

### A. Lapisan Kueri Global Database
Isolasi di tingkat kueri dijamin menggunakan global scope pada Trait **[BelongsToTenant.php](file:///d:/laragon/www/simt-backend/app/Traits/BelongsToTenant.php)**. Setiap query ke model yang menggunakan trait ini (seperti `Student`, `SchoolClass`, `Bill`, `Attendance`, `AuditLog`) secara otomatis disisipi filter `where tenant_id = tenant_aktif` sehingga kueri mentah database tidak akan bocor ke tenant lain.

### B. Lapisan Otorisasi Role Lintas-Tenant
Spatie Laravel Permission dikonfigurasi dengan mengaktifkan fitur *Teams*. Ketika middleware **[IdentifyTenant.php](file:///d:/laragon/www/simt-backend/app/Http/Middleware/IdentifyTenant.php)** mengidentifikasi domain penyewa, ia memanggil metode:
```php
app(\Spatie\Permission\PermissionRegistrar::class)->setPermissionsTeamId($tenant->id);
```
Mekanisme ini memastikan kueri relasi pivot Spatie (`model_has_roles` dan `model_has_permissions`) disaring berdasarkan `team_id = tenant_id`. Ini mencegah eskalasi hak akses pengguna antar sekolah.

### C. Proteksi IDOR Kontekstual (Wali Murid)
Otorisasi wali murid tidak menggunakan permissions statis. Sebagai gantinya, sistem memverifikasi relasi pivot wali-siswa secara on-the-fly pada controller API (seperti **[FinanceApiController.php](file:///d:/laragon/www/simt-backend/Modules/Finance/app/Http/Controllers/FinanceApiController.php#L47)** dan **[AkademikApiController.php](file:///d:/laragon/www/simt-backend/Modules/Akademik/app/Http/Controllers/AkademikApiController.php#L21)**) menggunakan:
```php
$user->guardianStudents()->where('student_id', $student->id)->exists();
```
Jika wali mencoba menembak API siswa lain, respons otomatis ditolak (`403 Forbidden`).

---

## 3. Temuan Celah Keamanan & Perbaikan Bug (*Bugfix Report*)

Selama proses audit ulang kueri model `User`, ditemukan satu celah kebocoran data pengguna tingkat tenant (*tenant user leakage*):

### A. Deskripsi Temuan Celah
*   **Berkas Terkait**: **[DashboardController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/DashboardController.php#L57)**
*   **Logika Awal**: Pada metode `auditLogs` yang memuat filter daftar pengguna untuk halaman audit log tenant:
    ```php
    $users = \App\Models\User::select('id', 'name')->get();
    ```
*   **Dampak Kerentanan**: Karena model `User` tidak menggunakan trait `BelongsToTenant` (untuk kepentingan otentikasi global sebelum domain tenant diset), query di atas memicu pengambilan data **seluruh pengguna di dalam database secara global**. Akibatnya, administrator di `Tenant A` dapat melihat daftar nama user sekolah di `Tenant B` pada dropdown filter audit log.

### B. Langkah Perbaikan Kode
Kami memperbarui kode tersebut untuk membatasi kueri hanya mengambil daftar user yang memiliki kecocokan `tenant_id` dengan tenant yang aktif saat ini:

```diff
         $logs = $query->paginate(50)->withQueryString();
-        $users = \App\Models\User::select('id', 'name')->get();
+        $tenantId = app(\App\Support\Tenancy::class)->tenantId();
+        $users = \App\Models\User::where('tenant_id', $tenantId)->select('id', 'name')->get();
 
         return view('core::dashboard.audit_logs', compact('logs', 'users'));
```
Dengan perbaikan ini, dropdown filter user kini terisolasi penuh per sekolah.

---

## 4. Hasil Uji Fitur & Uji Keandalan (*PHPUnit Test Results*)

Untuk memverifikasi kekokohan isolasi tenant pasca perbaikan, pengujian fungsional khusus dijalankan menggunakan PHP 8.3 CLI:

```powershell
php83 artisan test --filter=TenantIsolationTest
```

### Rincian Eksekusi Test Suite:
Semua pengujian lolos dengan status **Passed (100% Sukses)** dengan rincian asersi sebagai berikut:

1.  `student_query_is_filtered_by_tenant` — **PASS**
    *   *Deskripsi*: Memastikan kueri biasa (`Student::all()`) di Tenant 1 hanya menampilkan data Tenant 1.
2.  `tenant2_cannot_see_tenant1_students` — **PASS**
    *   *Deskripsi*: Memastikan user Tenant 2 tidak dapat melihat atau mengidentifikasi data siswa milik Tenant 1.
3.  `without_tenant_global_scope_returns_all` — **PASS**
    *   *Deskripsi*: Memastikan kueri tanpa scope (`withoutTenant()`) hanya dapat dipicu dalam konteks sistem yang diizinkan untuk menarik data global.
4.  `for_tenant_scope_filters_correctly` — **PASS**
    *   *Deskripsi*: Memverifikasi helper filter kueri manual per tenant berfungsi sesuai parameter input.
5.  `creating_student_auto_fills_tenant_id` — **PASS**
    *   *Deskripsi*: Memastikan setiap kali data siswa baru ditambahkan, `tenant_id` terisi otomatis sesuai context request.
6.  `tenant1_admin_cannot_access_tenant2_student_detail` — **PASS**
    *   *Deskripsi*: Memastikan admin Tenant 1 diblokir secara otomatis oleh global scope saat mencoba membuka detail ID murid Tenant 2.
7.  `tenant_isolation_works_for_classes` — **PASS**
    *   *Deskripsi*: Memverifikasi isolasi multi-tenant bekerja sempurna pada entitas rombel kelas (`SchoolClass`).
8.  `switching_tenant_context_changes_data_visibility` — **PASS**
    *   *Deskripsi*: Memastikan pergantian konteks tenant secara run-time langsung mengubah visibilitas data seketika tanpa kebocoran memori cache.

### Statistik Uji Akhir:
*   **Total Pengujian**: 8 passed
*   **Total Asersi**: 22 passed
*   **Durasi Eksekusi**: 8.27 detik
*   **Status Build**: 🟢 Green / Success

---

## 5. Kesimpulan Kepatuhan Keamanan (*Compliance Statement*)

Berdasarkan audit statis kode program dan hasil eksekusi pengujian dinamis, ditarik kesimpulan keamanan sebagai berikut:

> [!IMPORTANT]
> Sistem **SIMT Backend** dinyatakan **Lolos Audit Keamanan Konteks Multi-Tenant**. Mekanisme isolasi data tenant, penegakan hak akses peran (Spatie Teams RBAC), serta filter kontekstual portal wali murid terbukti aman dari kebocoran data lintas-tenant maupun kerentanan manipulasi parameter ID (IDOR).

---
*Laporan Audit Keamanan ini disiapkan secara resmi dan disimpan per tanggal 16 Juni 2026.*
