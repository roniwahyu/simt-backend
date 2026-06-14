# ARSITEKTUR MODUL — CORE (INTI) vs PLUG & PLAY
## SIMT MTs — Pemetaan Modul Mandatory vs Opsional

**Tanggal:** 14 Juni 2026
**Sumber kebenaran:** Doc 28 (Modular MVC), Doc 29 (API General vs Plugins), Doc 38/39 (Scope MVP), Doc 53 (Context terakhir)
**Status repo saat ini:** Laravel 11 + nwidart/laravel-modules 13 (DIPERTAHANKAN — plug & play sejati sudah aktif)

---

## 0. JAWABAN SINGKAT

**Masih pakai nwidart?** ✅ **YA.** nwidart/laravel-modules tetap dipakai sebagai mesin modular. Sudah diverifikasi plug & play sejati: nonaktifkan modul di `modules_statuses.json` → route/view/provider-nya ikut hilang.

**Mana yang INTI (tak bisa dilepas)?**
> **`Core`** — satu-satunya modul yang TIDAK boleh dimatikan. Berisi Tenant, RBAC, Auth, Dashboard, Super-Admin. Tanpa Core, sistem tidak bisa login & tidak ada konteks tenant.

**Mana yang Plug & Play penuh?**
> Semua modul selain Core. Untuk MVP saat ini: **`Student`, `Attendance`, `Finance`**. Untuk roadmap penuh (Doc 28): **`Tahfiz`, `Inclusion`, `Counseling`, `EOffice`, `Library`, `Facility`, `Academic`, `HR`**.

---

## 1. DUA LAPISAN MODULARITAS (PENTING — JANGAN TERTUKAR)

Sistem ini punya **dua** mekanisme on/off yang berbeda tujuan:

| Lapisan | Dikontrol oleh | Cakupan | Tujuan |
|---|---|---|---|
| **A. Kode (nwidart)** | `modules_statuses.json` | Global (semua tenant) | Developer pasang/lepas modul dari codebase. Disable → provider, route, view modul tidak ter-load sama sekali. |
| **B. Langganan (tenant_modules)** | Tabel `tenant_modules` + middleware `module.active:{Kode}` | Per-tenant | Komersial: sekolah A beli Finance, sekolah B tidak. Disable → menu hilang + API `403 MODULE_INACTIVE`. |

**Aturan praktis:**
- `Core` = **selalu `true` di lapisan A**, dan **tidak pernah** di-gate `module.active` di lapisan B.
- Modul plug & play = `true` di lapisan A (kode terpasang di semua server), tapi **on/off per sekolah** lewat lapisan B (`tenant_modules`).

> Inilah strategi Doc 28 §5: *"kode modul tetap ada di server semua madrasah, tapi tabel kosong/tak diakses bila fitur disabled, dan menu hilang dari sidebar."*

---

## 2. KLASIFIKASI RESMI (Doc 28 §1)

### A. CORE & MANDATORY (Selalu Aktif — tak bisa dilepas)
| # | Modul | Isi | Status MVP |
|---|---|---|---|
| 1 | **`Core`** | Tenant, RBAC Spatie (teams), Auth/Login, Dashboard, Super-Admin Vendor | ✅ ADA & aktif |
| 2 | `Academic` | Bab 1: Kurikulum, Jadwal, **Presensi**, Rapor, Modul Ajar | ⏳ MVP: hanya Presensi (jadi modul `Attendance` terpisah) |
| 3 | `Student` | Bab 2 & 10: Biodata Siswa, Ekskul | ✅ ADA (Kesiswaan + Import Excel) |
| 4 | `HR` | Bab 11: SDM / Kepegawaian | 🔜 Post-MVP |

### B. PLUG & PLAY (Opsional, dijual terpisah)
| # | Modul | Isi | Status MVP |
|---|---|---|---|
| 5 | `Tahfiz` | Bab 3: Hafalan, Ubudiyah, Munaqosah | 🔜 Post-MVP (placeholder "Segera Hadir") |
| 6 | **`Finance`** | Bab 4: Tagihan SPP, Pembayaran, Kwitansi | ✅ ADA (lite) |
| 7 | `Inclusion` + `Counseling` | Bab 7 & 9: ABK/PPI, BK | 🔜 Post-MVP |
| 8 | `EOffice` | Bab 13: Arsip, Surat, Disposisi, E-Signature | 🔜 Post-MVP |
| 9 | `Library` | Bab 12: Perpustakaan | 🔜 Post-MVP |
| 10 | `Facility` | Bab 8: Sarpras & Inventaris | 🔜 Post-MVP |

> Catatan penting: **Di Doc 28, `Finance` adalah Plug & Play** (bukan core). Doc 29 juga menempatkan Finance di "Plug & Play Modules". Maka MVP mengikuti: Finance = modul opsional.

---

## 3. PEMETAAN SCOPE MVP (Doc 38/39) → MODUL nwidart

MVP menyederhanakan 13 modul menjadi 4 modul nyata + 1 portal:

| Modul nwidart | Tipe | Fitur MVP | Doc |
|---|---|---|---|
| **`Core`** | 🔒 INTI | Multi-tenant, RBAC 6 role, Login (web+API), Dashboard, Super-Admin | FR-C01..C08 |
| **`Student`** | 🔌 Plug & Play | CRUD Siswa/Kelas/TA, Import Excel 3-langkah, akun wali otomatis | FR-S01..S04 |
| **`Attendance`** | 🔌 Plug & Play | Grid presensi ≤60 dtk, edit+audit, rekap bulanan, hook WA, API portal | FR-P01..P06 |
| **`Finance`** | 🔌 Plug & Play | Tagihan SPP massal, catat bayar, kwitansi PDF, pengingat WA | FR-K01..K04 |
| *(Portal Ortu)* | repo terpisah (Next.js) | Login wali, kalender presensi, tagihan, PWA | FR-O01..O05 |

---

## 4. KONDISI REPO — SUDAH SESUAI TARGET ✅ (update 14 Juni 2026)

| Aspek | Status Final | Bukti |
|---|---|---|
| Modul nwidart fisik | `Core`, `Student`, `Attendance`, `Finance` (4 modul) | `module:list` semua [Enabled] |
| `modules_statuses.json` | `{Core, Student, Attendance, Finance}` = true | terverifikasi |
| FinanceController | dipindah ke `Modules/Finance/` (modul sendiri) | namespace `Modules\Finance\...` |
| Gating `module.active:Finance` | di route Finance modul sendiri | `Modules/Finance/routes/web.php` |
| Plug & play KODE (nwidart) | ✅ disable Finance → 4 route hilang, enable → kembali | diuji live |
| Plug & play TENANT (`tenant_modules`) | ✅ T1: 4 modul; T2: Core+Student → /attendance & /finance = **403** | diuji live |
| Core di-gate module.active? | ❌ Tidak (benar — Core selalu aktif) | OK |
| Route API modul | via RouteServiceProvider (prefix `api/v1`) konsisten | `api/v1/students`, dll |
| Test suite | **23 passed (51 assertions)** | `php artisan test` |

**Hasil:** Finance kini modul nwidart **mandiri** dan plug & play sejati. Bukti dua lapisan modularitas:
- **Lapisan kode:** `php artisan module:disable Finance` → semua route Finance hilang dari aplikasi (semua tenant).
- **Lapisan langganan:** tenant T2 (tanpa langganan Finance) akses `/finance/bills` → **HTTP 403**, sementara T1 (berlangganan) → **HTTP 200**.

---

## 5. ATURAN EMAS (untuk pengembangan modul berikutnya)

1. **Core tidak pernah di-gate** dengan `module.active`. Semua modul lain WAJIB di-gate.
2. Setiap modul plug & play baru: `php artisan module:make {Nama}` → daftarkan kode di `tenant_modules` → bungkus route dengan `module.active:{Nama}` → sembunyikan menu sidebar dengan `@if(app('currentTenant')?->hasModule('{Nama}'))`.
3. **Komunikasi antar-modul lewat Event/Listener**, bukan query langsung (Doc 28 §3) — agar modul yang mati tidak bikin crash.
4. Provider modul **TIDAK** didaftarkan manual di `bootstrap/providers.php` — biarkan nwidart yang kelola via `modules_statuses.json` (plug & play sejati).
5. Route API modul lewat `RouteServiceProvider` modul (prefix `api` + `/v1`), konsisten dengan Core.

---

*Dokumen ini adalah acuan arsitektur modul. Core = jantung sistem (tak terlepas). Sisanya = organ opsional yang bisa dipasang/lepas per sekolah maupun per-codebase.*
