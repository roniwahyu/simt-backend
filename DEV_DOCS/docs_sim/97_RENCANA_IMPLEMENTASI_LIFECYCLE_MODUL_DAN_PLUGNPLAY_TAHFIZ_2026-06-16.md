# Rencana Implementasi: Siklus Hidup Modul Plug & Play & Integrasi Tahfiz (Database-Driven)

Rencana ini merinci desain arsitektur untuk memasang, mengintegrasikan, dan melepas modul (khususnya modul `Tahfiz`) secara dinamis pada **simt-backend** dengan pendekatan *database-driven* demi menghindari gangguan sistem, file locks, dan kehilangan data pada lingkungan SaaS multi-tenant.

## 1. Analisis & Jawaban atas Umpan Balik Arsitektur

> [!IMPORTANT]
> **Mengapa Pendekatan File-Driven (nwidart `modules_statuses.json`) Berisiko untuk Produksi?**
> Jika mengaktifkan/menonaktifkan modul dengan menulis ulang file `modules_statuses.json` secara real-time via aplikasi:
> 1. **Flicker/Downtime**: Menulis file atau me-reload konfigurasi di server web dapat memicu restart PHP-FPM atau cache reset yang menyebabkan koneksi terputus sesaat (*flicker*).
> 2. **Masalah Multi-Server (Load Balancer)**: Perubahan file lokal di Server A tidak akan tersinkronisasi ke Server B secara otomatis.
> 3. **Izin Akses File**: Mengharuskan proses web (www-data) memiliki izin menulis ke root directory aplikasi, yang memicu celah keamanan.

> [!TIP]
> **Solusi Terbaik: Pendekatan Database-Driven (True SaaS)**
> 1. **Lapisan Kode (Static Deployment)**: Semua kode modul (seperti `Modules/Tahfiz`) di-deploy ke server dan selalu berstatus `true` di `modules_statuses.json` secara global.
> 2. **Lapisan Kontrol (Database-Driven)**: 
>    - Akses modul diatur sepenuhnya oleh database di tabel `tenant_modules`.
>    - Aktivasi/deaktivasi per tenant dilakukan hanya dengan mengubah nilai `active = true/false` pada baris database dengan satu klik di dashboard admin.
>    - Middleware `module.active` dan API Controller membaca status langsung dari database. Pendekatan ini aman, instan, tanpa restart server, tanpa *glitch*, dan kompatibel dengan arsitektur load-balanced.
> 3. **Uninstall Tanpa Kehilangan Data (Soft Deactivation)**:
>    - Saat modul "di-uninstall" untuk tenant tertentu, kita cukup menonaktifkannya di `tenant_modules`. 
>    - Data fisik (seperti `tahfiz_records`) tetap tersimpan aman di database sehingga jika tenant berlangganan kembali di masa depan, data lama mereka tidak hilang.

---

## 2. Perubahan yang Diusulkan

### A. Struktur Modul Tahfiz (Plug & Play)

#### [NEW] [Modules/Tahfiz](file:///d:/laragon/www/simt-backend/Modules/Tahfiz)
Membuat direktori modul baru `Modules/Tahfiz` dengan struktur nwidart standar:
*   `module.json`: Metadata modul Tahfiz.
*   `composer.json`: Autoloading namespace `Modules\Tahfiz\`.
*   `app/Providers/TahfizServiceProvider.php`: Mendaftarkan views dan RouteServiceProvider.
*   `app/Providers/RouteServiceProvider.php`: Memetakan rute web & api.
*   `routes/web.php`: Rute web internal, dilindungi middleware `['auth', SetTenantFromUser::class, 'module.active:Tahfiz']`.
*   `routes/api.php`: Rute API internal, dilindungi middleware `['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Tahfiz']`.
*   `app/Http/Controllers/TahfizController.php`: Halaman web dashboard tahfiz.

#### [MODIFY] [composer.json (Root)](file:///d:/laragon/www/simt-backend/composer.json)
Mendaftarkan namespace `Modules\Tahfiz\` ke PSR-4 autoloading root:
```json
"Modules\\Tahfiz\\": "Modules/Tahfiz/app/",
"Modules\\Tahfiz\\Database\\Seeders\\": "Modules/Tahfiz/database/seeders/"
```

---

### B. Pengelolaan Migrasi Database Modular

#### [MODIFY] [2026_06_16_000002_create_portal_ortu_tables.php](file:///d:/laragon/www/simt-backend/database/migrations/2026_06_16_000002_create_portal_ortu_tables.php)
*   Menghapus pembuatan tabel `tahfiz_records` dari migrasi portal global untuk dipindahkan ke folder modul `Tahfiz` sendiri.

#### [NEW] [Modules/Tahfiz/database/migrations/2026_06_16_000003_create_tahfiz_records_table.php](file:///d:/laragon/www/simt-backend/Modules/Tahfiz/database/migrations/2026_06_16_000003_create_tahfiz_records_table.php)
*   Membuat migrasi internal modul Tahfiz untuk skema tabel `tahfiz_records`.

---

### C. Gating Integrasi pada REST API

#### [MODIFY] [PortalOrtuApiController.php](file:///d:/laragon/www/simt-backend/Modules/Core/app/Http/Controllers/PortalOrtuApiController.php)
*   Memperkuat pengecekan keaktifan modul `Tahfiz` sebelum memproses query database:
    ```php
    use Nwidart\Modules\Facades\Module;
    
    // Cek apakah modul aktif secara global (file code) DAN aktif untuk tenant ini (database-driven)
    $hasTahfiz = Module::isEnabled('Tahfiz') && $tenant->hasModule('Tahfiz');
    ```

---

### D. Custom Command untuk Lifecycle Modul

#### [NEW] [ManageModule.php](file:///d:/laragon/www/simt-backend/app/Console/Commands/ManageModule.php)
Membuat perintah Artisan `php artisan simt:module {action} {module}`:
1.  **`install`**:
    *   Mengecek ketersediaan folder modul.
    *   Mengaktifkan modul secara global di `modules_statuses.json`.
    *   Menjalankan migrasi database modul (`php artisan module:migrate {module}`).
    *   Mendaftarkan status default langganan di `tenant_modules` untuk semua tenant.
2.  **`uninstall`**:
    *   Melakukan rollback migrasi database modul (`php artisan module:migrate-reset {module}`).
    *   Menonaktifkan modul secara global di `modules_statuses.json`.
    *   Mengubah status `active` menjadi `false` di `tenant_modules` untuk semua tenant.
3.  **`status`**:
    *   Melihat status modul baik di tingkat kode (file) maupun tingkat tenant (database).

---

## 3. Rencana Verifikasi

### Pengujian Otomatis (Unit & Feature Tests)
Membuat file pengujian **`tests/Feature/ModuleLifecycleTest.php`** untuk menguji siklus hidup modular secara komprehensif:
1.  **Uji Install**: Memastikan command install berhasil mengubah file status, melakukan migrasi database, dan mengisi tabel `tenant_modules`.
2.  **Uji Gating REST API**: Memastikan ketika status `active` diubah menjadi `false` pada database (`tenant_modules`), endpoint API modul langsung mengembalikan respon `403 MODULE_INACTIVE` secara instan tanpa restart.
3.  **Uji Fallback Dashboard**: Memastikan ketika modul dinonaktifkan, data dashboard murid/ortu tetap berjalan anggun (*graceful fallback*) dengan mengembalikan array kosong tanpa crash.
4.  **Uji Uninstall**: Memastikan command uninstall berhasil mereset tabel database modul dan menonaktifkan status modul secara bersih.
