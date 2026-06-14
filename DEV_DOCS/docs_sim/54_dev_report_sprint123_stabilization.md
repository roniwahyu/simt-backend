# DEV REPORT — STABILISASI SPRINT 1-2-3 + MODULARISASI FINANCE
## SIMT MTs — Laporan Eksekusi & Handover Konteks untuk Agent AI Berikutnya

**Tanggal:** 14 Juni 2026
**Penulis:** Agent Arena (sesi stabilisasi)
**Repo:** `haisyamalawwab/simt-backend` (branch `main`)
**Dokumen sebelumnya:** Doc 52 (Verifikasi), Doc 53 (Context Session 4)
**Status akhir:** ✅ Sprint 1 & 2 SELESAI TOTAL · Sprint 3 TERIMPLEMENTASI · siap lanjut Sprint 4 (WA Gateway)

> **UNTUK AGENT AI BERIKUTNYA:** Dokumen ini adalah sumber kebenaran konteks terkini. Baca penuh sebelum melanjutkan. Semua klaim di sini sudah **diverifikasi dengan menjalankan kode** (composer install → migrate:fresh --seed → php artisan test → smoke test API & web live), bukan asumsi.

---

## 0. RINGKASAN EKSEKUTIF (TL;DR)

Sesi ini melanjutkan dari Doc 52/53. Repo GitHub `main` sebenarnya sudah berisi rekonstruksi Sprint 1-2, TAPI memiliki **3 bug runtime kritis** yang membuat sebagian besar halaman 500. Semua sudah diperbaiki, ditambah penyelesaian Sprint 3 dan modularisasi Finance.

**Keputusan teknis utama (dari user):**
1. **Laravel 11 dipertahankan** (bukan Laravel 13 — terlalu dini/tinggi secara teknis). Kode sudah L11 (`^11.31`).
2. **DB:** SQLite untuk dev/test, MySQL untuk produksi (user akan update `.sql` dari migration MySQL).
3. **Controller:** sumber kebenaran = `Modules/` (nwidart); duplikat legacy di `app/Http/Controllers/Web|Api` DIHAPUS.
4. **Urutan kerja:** bugfix kritis → selesaikan S1/S2 + test hijau → rapikan S3.

**Hasil terverifikasi:**
- ✅ Test: **23 passed (51 assertions)** — naik dari 3 failed/15 passed sebelumnya.
- ✅ Semua halaman web & endpoint API: **HTTP 200, zero error**.
- ✅ Plug & play 2 lapisan terbukti (kode nwidart + langganan per-tenant).
- ✅ 4 modul nwidart: Core (inti) + Student, Attendance, Finance (plug & play).

---

## 1. KONTEKS PROYEK (untuk agent baru)

- **Produk:** SIMT MTs — Micro-SaaS B2B2C manajemen madrasah (MTs/SMP yayasan).
- **Model bisnis:** Rp 2.000/siswa/bln, min Rp 200rb/bln, prepaid 1 semester. Budget MVP Rp 5jt / 3 bulan.
- **Arsitektur:** Single-DB multi-tenant (`tenant_id` + Global Scope) · RBAC Spatie teams (`team_id = tenant_id`) · Hybrid Blade (admin/guru) + Next.js (portal ortu) · WA Gateway Baileys self-hosted (zero-cost).
- **Stack terkunci:** Laravel 11 + MySQL (prod) / SQLite (test) + Redis + nwidart/laravel-modules + Sanctum + Spatie Permission + Maatwebsite Excel + Barryvdh DomPDF.

---

## 2. TIGA BUG KRITIS YANG DIPERBAIKI

### BUG #1 🔴 `app('currentTenant')` tidak pernah di-bind → HTTP 500 di mana-mana
**Gejala:** `/api/v1/ping`, `/dashboard`, `/attendance`, `/finance/*`, cetak kwitansi → semua 500 (`Target class [currentTenant] does not exist`).
**Akar masalah:** Trait & middleware sudah dimigrasi ke singleton `Tenancy`, tapi 13 call-site di controller masih pakai binding lama `app('currentTenant')` yang sudah dihapus (mandat Doc 53).
**Perbaikan:**
1. Controller modul (Core, Attendance, Finance) → diganti `app(\App\Support\Tenancy::class)->tenant()`.
2. Jembatan kompatibilitas di `AppServiceProvider`: `$this->app->bind('currentTenant', fn ($app) => $app->make(Tenancy::class)->tenant())` — safety net agar tidak ada regresi mendadak.

### BUG #2 🔴 NISN/NIS tidak unik per-tenant → integritas data bocor
**Gejala:** Test `student_nis_is_unique_per_tenant` GAGAL; bisa input siswa NISN duplikat.
**Akar masalah:** Migration `students` hanya `index(['tenant_id','nis'])`, bukan `unique`.
**Perbaikan:**
1. Migration: `unique(['tenant_id','nis'])` + `unique(['tenant_id','nisn'])` (NULL diperlakukan distinct → siswa tanpa NIS tetap boleh banyak).
2. Validasi `Rule::unique(...)->where('tenant_id', $tenantId)->ignore($id)` di `StudentController::store/update` (error ramah, bukan exception DB).

### BUG #3 🟠 Split-brain controller (duplikasi `app/` vs `Modules/`)
**Akar masalah:** Controller terduplikasi di `app/Http/Controllers/Web|Api/` DAN `Modules/`. Route pakai versi Modules; versi `app/` orphan.
**Perbaikan:** Hapus `app/Http/Controllers/Web/` & `app/Http/Controllers/Api/` (hanya sisakan base `Controller.php`).

### BUG TAMBAHAN (ditemukan saat menulis test) 🟠 Double-submit presensi melanggar unique(student,date)
**Akar masalah:** `Attendance::updateOrCreate(['student_id','date'])` gagal cocok karena cast `date` menyimpan `2026-06-14 00:00:00`, sedang query pakai `2026-06-14` → INSERT duplikat → unique constraint violation saat edit presensi.
**Perbaikan:**
1. Model `Attendance`: cast `'date' => 'date:Y-m-d'`.
2. Controller: `$date = Carbon::parse($request->input('date'))->toDateString()` sebelum updateOrCreate.

---

## 3. PENYELESAIAN SPRINT (sesuai Doc 40)

### Sprint 1 — Foundation (Tenancy + RBAC) ✅ TOTAL
- `Tenancy` singleton, `BelongsToTenant` trait, middleware priority (IdentifyTenant/SetTenantFromUser SEBELUM SubstituteBindings) — semua benar.
- Login web (session) + API (Sanctum token 30 hari) — **terverifikasi 200**.
- `/api/v1/ping`: valid tenant → 200; unknown → 400 TENANT_NOT_FOUND.
- TenantIsolationTest: 8 test hijau.

### Sprint 2 — Kesiswaan + Import Excel ✅ TOTAL
- CRUD Siswa lengkap + **view yang sebelumnya HILANG dibuat**: `admin/student/index|create|edit.blade.php`.
- Import wizard 3-langkah (`StudentImportService`) berfungsi.
- `PitchingDemoSeeder`: 2 tenant, 106 siswa, 100+ wali, 100 attendance, 100 bills — jalan via pipeline import nyata.
- NISN unik per-tenant di-enforce (DB + validasi).
- StudentModuleTest: 8 test hijau.

### Sprint 3 — Presensi (UI + Rekap) ✅ TERIMPLEMENTASI
- Grid presensi per kelas (default Hadir, tap toggle, bulk save) — view sudah ada & berfungsi 200.
- **Method `classGrid` yang HILANG ditambahkan** (route `/attendance/class/{class}`).
- **View rekap bulanan yang HILANG dibuat**: `admin/attendance/rekap.blade.php` (grid tanggal + total H/A/I/S/T).
- Audit `marked_by` terisi guru penginput.
- Query rekap diubah dari `DATE_FORMAT` (MySQL-only) → `whereBetween` portable (SQLite+MySQL).
- API portal `GET /api/v1/students/{student}/attendances?month=` aktif.
- **AttendanceModuleTest BARU: 5 test hijau** (sebelumnya Sprint 3 tanpa test).

---

## 4. ARSITEKTUR MODUL — CORE vs PLUG & PLAY (FINAL)

Lihat detail di `DEV_DOCS/ARSITEKTUR_MODUL_CORE_vs_PLUGNPLAY.md`. Ringkasan:

### 4 Modul nwidart sekarang
| Modul | Tipe | Isi | `modules_statuses.json` |
|---|---|---|---|
| **Core** | 🔒 INTI (tak bisa dilepas) | Tenant, RBAC, Auth, Dashboard, Super-Admin | true (selalu) |
| **Student** | 🔌 Plug & Play | CRUD siswa/kelas/TA, import Excel | true |
| **Attendance** | 🔌 Plug & Play | Grid presensi, rekap, hook WA | true |
| **Finance** | 🔌 Plug & Play | Tagihan SPP, bayar, kwitansi PDF, pengingat WA | true |

> **Perubahan penting:** Finance DIPISAH dari Attendance jadi modul nwidart mandiri (`Modules/Finance/`) sesuai mandat Doc 28 (Finance = Plug & Play terpisah). Sebelumnya FinanceController "menumpang" di module Attendance.

### Dua lapisan modularitas (KEDUANYA terverifikasi)
1. **Lapisan KODE (nwidart):** `modules_statuses.json` — `module:disable Finance` → semua route Finance hilang dari aplikasi (uji: 4 route → 0 → 4).
   - Provider modul TIDAK lagi di-force-register di `bootstrap/providers.php`; nwidart yang kelola (plug & play sejati).
2. **Lapisan LANGGANAN (`tenant_modules`):** middleware `module.active:{Kode}` — per-sekolah.
   - Uji live: T1 (langganan Finance) → `/finance/bills` **200**; T2 (Core+Student saja) → `/attendance` & `/finance/bills` **403**.

### Aturan emas modul
- Core tidak pernah di-gate `module.active`.
- Modul plug & play: route via RouteServiceProvider modul (prefix `api/v1` konsisten dgn Core) + bungkus `module.active:{Nama}` + sembunyikan menu `@if(app('currentTenant')?->hasModule('{Nama}'))`.
- Komunikasi antar-modul via Event/Listener (jangan query langsung — modul mati tak boleh bikin crash).

---

## 5. PERUBAHAN NAMA TABEL: `classes` → `school_classes`

Sesuai mandat Doc 53 (hindari reserved word di MySQL produksi). Diubah di:
- `database/migrations/0001_..._000012` (create table), `000013` & `000014` (FK `constrained('school_classes')`).
- `app/Models/SchoolClass.php` → `protected $table = 'school_classes'`.
- Validasi `exists:classes,id` → `exists:school_classes,id`.
- Seeders/tests pakai model `SchoolClass` (transparan).

---

## 6. DAFTAR FILE YANG DIUBAH/DIBUAT

**Diperbaiki (modified):**
- `app/Providers/AppServiceProvider.php` — bind `currentTenant` bridge
- `app/Models/Attendance.php` — cast `date:Y-m-d`
- `app/Models/SchoolClass.php` — table `school_classes`
- `database/migrations/0001_..._000012/000013/000014` — rename tabel + unique NISN/NIS
- `Modules/Core/app/Http/Controllers/{AuthController,DashboardController}.php` — Tenancy singleton
- `Modules/Attendance/app/Http/Controllers/AttendanceController.php` — Tenancy, classGrid(), rekap portable, exists:school_classes
- `Modules/Attendance/app/Http/Controllers/AttendanceApiController.php` — rekap portable
- `Modules/Student/app/Http/Controllers/StudentController.php` — validasi unik NISN, exists:school_classes
- `Modules/{Student,Attendance}/app/Providers/*ServiceProvider.php` — register RouteServiceProvider
- `Modules/{Student,Attendance}/app/Providers/RouteServiceProvider.php` — BARU (api prefix + web group)
- `routes/web.php`, `Modules/*/routes/web.php` — web pakai SetTenantFromUser (bukan IdentifyTenant)
- `bootstrap/providers.php` — hapus force-register provider modul (biar nwidart kelola)
- `modules_statuses.json` — Core, Student, Attendance, Finance = true
- `composer.json` — autoload PSR-4 Finance
- `tests/Feature/TenantIsolationTest.php` — fix test rapuh (self-contained)

**Dibuat (created):**
- `Modules/Finance/` (modul nwidart lengkap: Controller dipindah dari Attendance + module.json + composer.json + 2 Provider + routes web/api)
- `resources/views/admin/student/{index,create,edit}.blade.php`
- `resources/views/admin/attendance/rekap.blade.php`
- `tests/Feature/AttendanceModuleTest.php` (5 test)
- `config/modules.php` (publish nwidart)
- `DEV_DOCS/ARSITEKTUR_MODUL_CORE_vs_PLUGNPLAY.md`
- `DEV_DOCS/54_dev_report_sprint123_stabilization.md` (dokumen ini)

**Dihapus (deleted):**
- `app/Http/Controllers/Web/` & `app/Http/Controllers/Api/` (duplikat legacy)

---

## 7. CARA REPRODUKSI & VERIFIKASI (untuk agent baru)

```bash
# 1. Setup (PHP 8.2+ dgn ext sqlite3, mbstring, xml, curl, zip, gd, bcmath)
composer install
cp .env.example .env && php artisan key:generate
touch database/database.sqlite   # DB_CONNECTION=sqlite di .env

# 2. Migrasi + seed demo
php artisan migrate:fresh --seed
#   → 11 migration, RolePermissionSeeder, PitchingDemoSeeder (106 siswa)

# 3. Test (HARUS 23 passed)
php artisan test

# 4. Verifikasi plug & play kode (nwidart)
php artisan module:list                  # 4 modul [Enabled]
php artisan module:disable Finance       # route finance hilang
php artisan module:enable Finance

# 5. Smoke test (login: ahmad@mts-alhikmah.sch.id / password)
php artisan serve
#   /api/v1/ping (header X-Tenant-Domain: mts-alhikmah) → 200
#   /dashboard, /students, /attendance, /finance/bills → 200
#   T2 (mts-annur) /attendance & /finance → 403 (tak langganan)
```

> ⚠️ **CATATAN LINGKUNGAN:** Folder `vendor/` (composer) bisa hilang antar-sesi karena di-exclude dari snapshot workspace. Jika `php artisan` error "Class Illuminate\Foundation\Application not found", jalankan `composer install` dulu.

---

## 8. AKUN DEMO (semua password: `password`)

| Akun | Login | Peran | Tenant |
|---|---|---|---|
| Vendor | `vendor@simt.id` | superadmin | lintas tenant |
| Ahmad | `ahmad@mts-alhikmah.sch.id` | admin_sekolah | T1 (Al-Hikmah, modul lengkap) |
| Ahmad | `ahmad@mts-annur.sch.id` | guru | T2 (An-Nur, Core+Student saja) |
| Wali | phone `628520000001` | wali (portal) | T1 |

---

## 9. NEXT — Sprint 4 (WA Gateway) & rencana user

**Rencana user:**
- [ ] User akan update `.sql` di repo dari migration terakhir (MySQL).
- [ ] Lanjut Sprint 3 finishing (export Excel rekap belum ada) → Sprint 4.

**Sprint 4 (Doc 40) — WA Gateway Baileys:**
- [ ] Service Node.js Baileys multi-session (start/qr/status/send), auth state per tenant.
- [ ] API key internal + systemd + auto-reconnect.
- [ ] Halaman "WA Connect" Blade (QR live poll, status sesi, reset).
- [ ] Laravel Queue `SendWaNotification` (sudah ADA sebagai Job) → integrasi rate-limit 10/mnt, jitter, retry 3×, log `wa_notifications`.
- [ ] Hook presensi → notif (sudah ADA di AttendanceController::store, perlu disambung ke gateway nyata).
- [ ] Kirim kredensial wali massal via WA (sudah diantrikan saat import).

**Sisa hutang teknis kecil (P1/P2, tidak blocker):**
- Export Excel rekap presensi (FR-P06) belum diimplementasi (UI rekap sudah ada).
- View modul Finance masih pakai `admin.finance.*` di root (boleh dipindah ke `Modules/Finance/resources/views`).
- `welcome.blade.php` ada typo `className=` (React-ism) — kosmetik.
- Tailwind via CDN (dev) — build Vite ditunda ke Sprint 6 (hardening).

---

*Dokumen ini ditulis agar agent AI berikutnya memiliki konteks yang SAMA dan akurat. Semua status diverifikasi langsung terhadap kode di `/home/user/simt-backend/` pada 14 Juni 2026.*
