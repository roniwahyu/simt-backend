# PANDUAN MEMBUAT MODUL PLUG & PLAY — SIMT MTs
## Langkah baku menambah modul nwidart baru (mis. Tahfiz, Library, EOffice)

**Tanggal:** 14 Juni 2026 · **Pola acuan:** modul `Finance` (dibuat di sesi stabilisasi)
**Prinsip:** Setiap modul opsional = modul nwidart mandiri, di-gate `module.active`, route via RouteServiceProvider, menu tersembunyi bila tak langganan.

> **Catatan:** Modul **Core** TIDAK mengikuti panduan ini — Core selalu aktif & tidak pernah di-gate `module.active`.

---

## 0. PRINSIP WAJIB (jangan dilanggar)

1. **Dua lapisan on/off:**
   - Kode: `modules_statuses.json` (dikelola nwidart, `module:enable/disable`).
   - Langganan: tabel `tenant_modules` + middleware `module.active:{Nama}`.
2. **Jangan** force-register provider modul di `bootstrap/providers.php` — biarkan nwidart yang kelola (plug & play sejati).
3. Route API modul via **RouteServiceProvider** (prefix `api` + `/v1`), konsisten dengan Core.
4. Komunikasi antar-modul lewat **Event/Listener**, bukan query langsung (modul mati tak boleh bikin crash).
5. Tabel domain modul wajib `tenant_id` + trait `BelongsToTenant`.

---

## 1. SCAFFOLD MODUL

```bash
php artisan module:make Tahfiz          # buat struktur nwidart
php artisan module:enable Tahfiz        # set modules_statuses.json = true
```

Atau manual (pola Finance) — buat struktur:
```
Modules/Tahfiz/
├── app/Http/Controllers/TahfizController.php
├── app/Providers/TahfizServiceProvider.php
├── app/Providers/RouteServiceProvider.php
├── module.json
├── composer.json
├── routes/web.php
├── routes/api.php
└── resources/views/
```

---

## 2. FILE WAJIB (template)

### `module.json`
```json
{
  "name": "Tahfiz",
  "alias": "tahfiz",
  "description": "Modul Tahfiz (Plug & Play) — Hafalan, Ubudiyah, Munaqosah",
  "priority": 4,
  "providers": ["Modules\\Tahfiz\\Providers\\TahfizServiceProvider"],
  "files": []
}
```

### `composer.json`
```json
{
  "name": "nwidart/tahfiz",
  "autoload": {
    "psr-4": {
      "Modules\\Tahfiz\\": "app/",
      "Modules\\Tahfiz\\Database\\Seeders\\": "database/seeders/"
    }
  }
}
```

### `app/Providers/TahfizServiceProvider.php`
```php
<?php
namespace Modules\Tahfiz\Providers;
use Illuminate\Support\ServiceProvider;

class TahfizServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->register(RouteServiceProvider::class);
    }
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/../../resources/views', 'tahfiz');
    }
}
```

### `app/Providers/RouteServiceProvider.php`
```php
<?php
namespace Modules\Tahfiz\Providers;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    protected string $name = 'Tahfiz';
    public function map(): void { $this->mapApiRoutes(); $this->mapWebRoutes(); }
    protected function mapWebRoutes(): void {
        Route::middleware('web')->group(module_path($this->name, '/routes/web.php'));
    }
    protected function mapApiRoutes(): void {
        Route::middleware('api')->prefix('api')->name('api.')->group(module_path($this->name, '/routes/api.php'));
    }
}
```

### `routes/web.php` (WAJIB di-gate `module.active`)
```php
<?php
use Illuminate\Support\Facades\Route;
use Modules\Tahfiz\Http\Controllers\TahfizController;
use App\Http\Middleware\SetTenantFromUser;

Route::middleware(['auth', SetTenantFromUser::class, 'module.active:Tahfiz'])->group(function () {
    Route::get('/tahfiz', [TahfizController::class, 'index'])->name('tahfiz.index');
    // ...
});
```

### `routes/api.php`
```php
<?php
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\IdentifyTenant;

Route::middleware(['auth:sanctum', IdentifyTenant::class, 'check.tenant.access', 'module.active:Tahfiz'])->group(function () {
    // Route::post('/v1/tahfiz/mutabaah', [TahfizApiController::class, 'store']);
});
```

---

## 3. DAFTARKAN AUTOLOAD (root `composer.json`)
Tambah di `autoload.psr-4`:
```json
"Modules\\Tahfiz\\": "Modules/Tahfiz/app/",
"Modules\\Tahfiz\\Database\\Seeders\\": "Modules/Tahfiz/database/seeders/"
```
Lalu:
```bash
composer dump-autoload -o
```

---

## 4. CONTROLLER — akses tenant & view

```php
namespace Modules\Tahfiz\Http\Controllers;
use Illuminate\Routing\Controller;

class TahfizController extends Controller
{
    public function index()
    {
        $tenant = app(\App\Support\Tenancy::class)->tenant();   // BUKAN app('currentTenant')
        // model domain otomatis ter-scope tenant via BelongsToTenant
        return view('admin.tahfiz.index', compact('tenant'));
    }
}
```

---

## 5. MIGRATION (tabel domain modul)

```php
Schema::create('tahfiz_records', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
    $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
    // ... kolom domain
    $table->timestamps();
    $table->index(['tenant_id', 'student_id']);
});
```
Model: `use App\Traits\BelongsToTenant;`

---

## 6. REGISTER MODULE CODE (langganan)

Tambah `module_code` saat seeding tenant (atau panel Super-Admin):
```php
TenantModule::firstOrCreate(
    ['tenant_id' => $tenant->id, 'module_code' => 'Tahfiz'],
    ['active' => true]
);
```

---

## 7. SEMBUNYIKAN MENU (sidebar Blade)

Di `resources/views/layouts/app.blade.php`:
```blade
@if(app('currentTenant')?->hasModule('Tahfiz'))
    <a href="{{ route('tahfiz.index') }}">Tahfiz</a>
@endif
```

---

## 8. TEST (wajib per modul)

Buat `tests/Feature/TahfizModuleTest.php` (pola `AttendanceModuleTest`):
- happy path simpan data
- isolasi tenant
- `module.active` nonaktif → **403**
- audit/uniqueness bila relevan

---

## ✅ CHECKLIST MODUL BARU

- [ ] `module.json` + `composer.json` + 2 Provider + routes web/api
- [ ] Autoload PSR-4 di root `composer.json` + `composer dump-autoload -o`
- [ ] `php artisan module:enable {Nama}` → cek `modules_statuses.json`
- [ ] Route di-gate `module.active:{Nama}`
- [ ] `module_code` terdaftar di `tenant_modules`
- [ ] Menu sidebar dibungkus `@if(...->hasModule('{Nama}'))`
- [ ] Controller pakai `Tenancy` singleton (bukan `currentTenant`)
- [ ] Tabel domain `tenant_id` + `BelongsToTenant`
- [ ] Test modul (termasuk 403 saat nonaktif)
- [ ] **Verifikasi plug & play:** `module:disable {Nama}` → `route:list` route hilang → `module:enable` → kembali

---

## VERIFIKASI PLUG & PLAY (dua lapisan)
```bash
# Lapisan kode (nwidart)
php artisan module:disable Tahfiz && php artisan route:clear
php artisan route:list | grep tahfiz      # → kosong
php artisan module:enable Tahfiz

# Lapisan langganan (per-tenant): set tenant_modules active=false → akses route → 403
```

---

*Pola ini sudah terbukti pada modul Finance. Ikuti agar konsisten & benar-benar plug & play.*
