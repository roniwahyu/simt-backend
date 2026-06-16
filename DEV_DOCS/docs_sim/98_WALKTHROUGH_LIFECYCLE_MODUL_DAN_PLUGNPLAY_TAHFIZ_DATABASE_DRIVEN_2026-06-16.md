# Walkthrough: Siklus Hidup Modul Plug & Play & Integrasi Tahfiz (Database-Driven)

**Tanggal:** 16 Juni 2026  
**Status:** Completed (Production Ready)  
**Nomor Dokumen:** 98_WALKTHROUGH_LIFECYCLE_MODUL_DAN_PLUGNPLAY_TAHFIZ_DATABASE_DRIVEN_2026-06-16  

Laporan ini mendokumentasikan hasil dari implementasi arsitektur modular *Plug & Play* berbasis database (*database-driven*) pada **simt-backend**, termasuk scaffold modul `Tahfiz`, pemisahan database migrasi modular, pembuatan custom console command untuk siklus hidup modul, integrasi REST API, dan pembuktian uji otomatis.

---

## 🛠️ Perubahan yang Dilakukan

### 1. Scaffold Modul Tahfiz (Plug & Play) [NEW]
Kami membuat modul nwidart mandiri untuk `Tahfiz` di bawah direktori [Modules/Tahfiz](file:///d:/laragon/www/simt-backend/Modules/Tahfiz):
*   **`module.json`**: Pendaftaran metadata modul dengan prioritas 4.
*   **`composer.json`**: Autoloading namespace `Modules\Tahfiz\` ke `app/`.
*   **`app/Providers/TahfizServiceProvider.php`**: Mendaftarkan `RouteServiceProvider`, memuat views, serta memuat path migrasi internal modul menggunakan `$this->loadMigrationsFrom()`.
*   **`app/Providers/RouteServiceProvider.php`**: Memetakan rute `/tahfiz` (web) dan `/api/v1/tahfiz` (api).
*   **`routes/web.php`**: Rute web dilindungi middleware `['auth', SetTenantFromUser::class, 'module.active:Tahfiz']`.
*   **`routes/api.php`**: Rute API dilindungi middleware `['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Tahfiz']`.
*   **`app/Http/Controllers/TahfizController.php`**: Menyajikan halaman index dashboard Tahfiz.
*   **`resources/views/index.blade.php`**: Tampilan awal Blade untuk modul Tahfiz.

### 2. Autoloading Root [MODIFY]
Mendaftarkan namespace modul `Tahfiz` ke psr-4 autoloading di [composer.json](file:///d:/laragon/www/simt-backend/composer.json) root:
```json
"Modules\\Tahfiz\\": "Modules/Tahfiz/app/",
"Modules\\Tahfiz\\Database\\Seeders\\": "Modules/Tahfiz/database/seeders/"
```

### 3. Pemisahan & Enkapsulasi Migrasi Database
*   **`[MODIFY] 2026_06_16_000002_create_portal_ortu_tables.php`**: Menghapus definisi tabel `tahfiz_records` dari migrasi portal global.
*   **`[NEW] Modules/Tahfiz/database/migrations/2026_06_16_000003_create_tahfiz_records_table.php`**: Menampung pembuatan skema tabel `tahfiz_records` di tingkat modul, lengkap dengan primary key string (UUID/CUID) untuk mendukung *local-first sync*.

### 4. Custom Artisan Command: Lifecycle Manager [NEW]
Membuat Artisan command di [ManageModule.php](file:///d:/laragon/www/simt-backend/app/Console/Commands/ManageModule.php) dengan signature `php artisan simt:module {action} {module}`:
*   **`install`**: Mengaktifkan status global modul di `modules_statuses.json`, memverifikasi composer autoload, mendaftarkan path migrasi secara dinamis ke migrator Laravel, menjalankan migrasi database modul (`module:migrate`), dan mengaktifkan status langganan tenant di `tenant_modules` untuk semua tenant.
*   **`uninstall`**: Melakukan rollback migrasi database modul (`module:migrate-reset`), menonaktifkan modul secara global di `modules_statuses.json`, dan me-nonaktifkan status langganan tenant di `tenant_modules` (`active = false`).
*   **`status`**: Menampilkan status keaktifan kode global (nwidart) dan daftar tenant yang berlangganan aktif dari database.

### 5. Integrasi Gating REST API & Seeder Resilien
*   **`[MODIFY] PortalOrtuApiController.php`**: Memperketat validasi data dashboard murid. Pengecekan data Tahfiz diverifikasi menggunakan gabungan status kode global (`Module::isEnabled('Tahfiz')`) dan status langganan database-driven (`$tenant->hasModule('Tahfiz')`). Jika tidak terpenuhi, portal mengembalikan data kosong secara anggun (*graceful fallback*).
*   **`[MODIFY] PitchingDemoSeeder.php`**: Membungkus seluruh seeder modular (schedules, violations, achievements, tahfiz, grade_details) dengan pengecekan `Schema::hasTable('{table}')`. Hal ini mencegah kegagalan seeder apabila modul dinonaktifkan di tingkat kode pada database fresh.

---

## 🧪 Hasil Pengujian & Verifikasi

### 1. Jalur Siklus Hidup Mandiri (Artisan Command)
*   **Status Awal**:
    ```bash
    $ php83 artisan simt:module status Tahfiz
    Global Code Level (nwidart): DISABLED
    Active Tenant Subscriptions: 0
    ```
*   **Proses Install**:
    ```bash
    $ php83 artisan simt:module install Tahfiz
    Installing module Tahfiz...
    Enabling module globally...
    Running module database migrations... (tahfiz_records created)
    Registering active subscriptions for all tenants...
    Module Tahfiz installed and integrated successfully!
    ```
*   **Proses Uninstall**:
    ```bash
    $ php83 artisan simt:module uninstall Tahfiz
    Uninstalling module Tahfiz...
    Rolling back module database migrations... (tahfiz_records dropped)
    Disabling module globally...
    Deactivating subscriptions for all tenants...
    Module Tahfiz uninstalled successfully.
    ```

### 2. Unit & Feature Testing
Kami membuat file pengujian khusus **[ModuleLifecycleTest.php](file:///d:/laragon/www/simt-backend/tests/Feature/ModuleLifecycleTest.php)** untuk menguji siklus instalasi, integrasi gating di REST API (uji respon data valid vs fallback data kosong saat dinonaktifkan per tenant), dan pencabutan modul.

**Hasil Eksekusi Uji:**
```powershell
$ php83 artisan test --filter=ModuleLifecycleTest
```
```
   PASS  Tests\Feature\ModuleLifecycleTest
  ✓ module lifecycle install uninstall and gating                                                               12.04s  

  Tests:    1 passed (17 assertions)
  Duration: 12.20s
```
Semua 17 asersi fungsionalitas (instalasi modul, pembuatan tabel, gating REST API, fallback data tanpa crash, dan pencabutan tabel) berhasil **PASS 100%**.

### 3. Rebuild Database & Seeder
Perintah `php83 artisan migrate:fresh --seed` berjalan sukses tanpa error (durasi seeding `67 detik`). Pengujian seeder yang resilient berhasil membuktikan bahwa proses seed tidak mengalami kegagalan meskipun database dikonfigurasi tanpa modul Tahfiz pada saat startup.
