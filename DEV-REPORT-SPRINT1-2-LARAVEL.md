# Dev Report — Sprint 1 & 2 Implementation (Laravel 11)
## SIMT MVP — Sistem Informasi Manajemen Terpadu Madrasah Tsanawiyah

**Tanggal:** 13 Juni 2026  
**Status:** ✅ SPRINT 1-2 SELESAI — LARAVEL 11 RUNTIME  
**Lokasi Kode:** `/home/user/SIMT-Laravel/`  
**Referensi:** Dokumentasi 48 Sesi `ANALISA_KELAYAKAN_SIMSEKOLAH`

---

## 1. Ringkasan Eksekusi

| Sprint | Goal | Output | Status |
|--------|------|--------|--------|
| **S1** | Foundation: Tenancy + RBAC + Auth + API | Laravel 11 berjalan di port 8000, SQLite, Spatie RBAC, Sanctum JWT | ✅ |
| **S2** | Kesiswaan: CRUD + Student API + Tenant Isolation | 30 siswa T1, 5 siswa T2, isolasi 403, module gate | ✅ |

---

## 2. Stack Teknologi (Production-Ready)

| Layer | Teknologi | Versi |
|-------|-----------|-------|
| **Backend** | Laravel | 11.x (PHP 8.4) |
| **Database** | SQLite | 3 (WAL mode) |
| **Auth** | Laravel Sanctum | 4.x (Bearer token, 30 hari) |
| **RBAC** | Spatie Permission | 6.x |
| **Excel** | Maatwebsite Excel | 3.x |
| **PDF** | Barryvdh DomPDF | 3.x |
| **Modular** | nwidart/laravel-modules | 13.x |
| **Queue** | Database (sync untuk dev) | — |

---

## 3. Arsitektur Multi-Tenant

```
Single-Database Multi-Tenancy (Row-Level Isolation)
├── tenant_id (BIGINT, indexed) di SEMUA tabel domain
├── Middleware: IdentifyTenant (X-Tenant-Domain / subdomain)
├── Middleware: CheckTenantAccess (token → tenant match)
├── Middleware: EnsureModuleActive (Plug & Play toggle)
└── Trait: BelongsToTenant (Global Scope + auto-fill)
```

---

## 4. ERD (12 Tabel MVP)

| Tabel | Fungsi | Tenant |
|-------|--------|--------|
| `tenants` | Data master sekolah | — |
| `tenant_modules` | Plug & Play flags | FK tenants |
| `users` | Semua user (admin, guru, wali) | FK tenants |
| `roles` + `permissions` + `model_has_*` | Spatie RBAC | — |
| `school_years` | Tahun ajaran | FK tenants |
| `classes` | Rombel / kelas | FK tenants |
| `students` | Data siswa | FK tenants |
| `class_student` | Pivot siswa-kelas-TA | — |
| `guardian_student` | Pivot wali-siswa | — |
| `attendances` | Presensi harian | FK tenants |
| `bills` | Tagihan SPP | FK tenants |
| `payments` | Pembayaran | FK tenants |
| `wa_notifications` | Log queue WA | FK tenants |
| `personal_access_tokens` | Sanctum tokens | — |

---

## 5. Middleware Chain

```
Request → IdentifyTenant → CheckTenantAccess → EnsureModuleActive → Controller
          (X-Tenant-Domain)   (token↔tenant)      (Plug & Play)
```

### Error Codes
| Kode | HTTP | Kondisi |
|------|------|---------|
| `TENANT_NOT_FOUND` | 400 | Subdomain/Header tidak dikenali |
| `TENANT_SUSPENDED` | 402 | Overdue > 14 hari |
| `FORBIDDEN_TENANT` | 403 | Token tidak cocok dengan tenant header |
| `MODULE_INACTIVE` | 403 | Fitur belum dibeli oleh tenant |

---

## 6. Hasil Test Runtime (Verifikasi)

### Test Script Output
```
T1 Students: 30
T2 Students: 5
Isolation (T1+T2): 403
T2 Finance Module: 403
Wali Children: 10
Superadmin: OK
```

### Gate-by-Gate Verification

| Gate | Test | Hasil | Keterangan |
|------|------|-------|------------|
| **G1** Health Check | `GET /api/health` | ✅ `{"status":"ok"}` | Server jalan |
| **G2** Tenant Ping | `GET /api/v1/ping` (X-Tenant-Domain: alhikmah) | ✅ `MTs Al-Hikmah` | Resolusi tenant dari header |
| **G3** T1 Login | `POST /api/v1/auth/login` (ahmad@alhikmah) | ✅ Token Sanctum | Auth + tenant context |
| **G4** T1 Students | `GET /api/v1/students` | ✅ 30 records | Model isolasi `tenant_id` |
| **G5** T2 Login | `POST /api/v1/auth/login` (ahmad@annur) | ✅ Token Sanctum | Tenant terpisah |
| **G6** T2 Students | `GET /api/v1/students` | ✅ 5 records | Model isolasi `tenant_id` |
| **G7** Isolation | T1 token + T2 header | ✅ 403 | `CheckTenantAccess` blokir |
| **G8** Module Inactive | T2 `/students/1/bills` | ✅ 403 | `EnsureModuleActive` blokir Finance di T2 |
| **G9** Wali Children | `GET /api/v1/me/children` | ✅ 10 anak | Relasi guardian_student |
| **G10** Superadmin | Login superadmin@simt.id | ✅ OK | Tenant-agnostic access |

---

## 7. Struktur File Kunci

```
SIMT-Laravel/
├── app/
│   ├── Traits/BelongsToTenant.php          # Global Scope + auto-fill tenant_id
│   ├── Http/Middleware/
│   │   ├── IdentifyTenant.php              # Header/Subdomain → tenant context
│   │   ├── CheckTenantAccess.php           # Token ↔ Tenant match (anti-bocor)
│   │   ├── EnsureModuleActive.php          # 403 MODULE_INACTIVE
│   │   └── SetTenantFromUser.php           # Web session tenancy
│   ├── Http/Controllers/Api/
│   │   ├── AuthController.php              # Login, me, children, logout
│   │   ├── StudentApiController.php        # list, show, bills
│   │   └── AttendanceApiController.php     # index (guardian-only)
│   ├── Models/
│   │   ├── Tenant.php                      # hasModule(), isSuspended()
│   │   ├── User.php                        # Sanctum + Spatie + BelongsToTenant
│   │   ├── Student.php                     # guardians(), classes()
│   │   └── [SchoolYear, SchoolClass, Attendance, Bill, Payment, WaNotification]
│   └── Jobs/SendWaNotification.php         # Queue skeleton (Sprint 4)
├── database/
│   ├── migrations/0001_01_01_000010-000016 # 12 tabel custom
│   ├── migrations/0001_01_01_000003       # Spatie permissions tables
│   ├── migrations/2024_01_01_000000       # Sanctum personal_access_tokens
│   └── seeders/
│       ├── RolePermissionSeeder.php        # 6 roles × 18 permissions
│       └── DemoTenantSeeder.php            # 2 tenant, 35 siswa, 5 user, 30 bill
├── routes/
│   ├── api.php                             # API endpoints + module gates
│   └── web.php                             # Blade admin panel routes
├── config/permission.php                   # Spatie config (teams=false)
└── .env                                    # SQLite, Asia/Jakarta, custom WA config
```

---

## 8. Demo Accounts (password: `simt2026`)

| Login | Role | Tenant | Modul Aktif |
|-------|------|--------|-------------|
| `superadmin@simt.id` | superadmin | — | Semua |
| `ahmad@alhikmah.simt.id` | kepala_madrasah | alhikmah | Core, Student, Attendance, Finance |
| `guru@alhikmah.simt.id` | guru | alhikmah | Core, Student, Attendance, Finance |
| `628520000001` | wali | alhikmah | Core, Student, Attendance, Finance |
| `ahmad@annur.simt.id` | guru | annur | Core, Student (❌ Attendance, Finance) |

---

## 9. Cara Menjalankan

```bash
cd /home/user/SIMT-Laravel
composer install                    # jika belum
php artisan serve --host=0.0.0.0 --port=8000

# Server: http://localhost:8000
# API Health:  GET http://localhost:8000/api/health
# API Login:   POST http://localhost:8000/api/v1/auth/login
# API Students: GET http://localhost:8000/api/v1/students
# Headers:      X-Tenant-Domain: alhikmah, Authorization: Bearer {token}, Accept: application/json
```

---

## 10. Keputusan Teknis Sprint Ini

| Keputusan | Implementasi |
|-----------|-------------|
| **Single-DB Multi-Tenant** | `tenant_id` di setiap tabel + `BelongsToTenant` Global Scope |
| **Tenant Resolution** | `X-Tenant-Domain` header (API) atau subdomain (Web) |
| **Anti-Bocor** | `CheckTenantAccess` middleware: token user WAJIB cocok dengan header tenant |
| **Plug & Play** | `tenant_modules` table + `EnsureModuleActive` middleware |
| **RBAC** | Spatie Permission 6.x dengan `role_user` pivot per tenant (manual) |
| **Auth** | Sanctum Bearer token, 30 hari expiry, login via email ATAU phone |
| **Data Demo** | SQLite single-file, 35 siswa, 2 tenant, 5 user, 30 tagihan |

---

## 11. Isu Ditemukan & Fix

| Isu | Penyebab | Fix |
|-----|----------|-----|
| Spatie migration tidak publish | Laravel 11 change | Buat migration manual `0001_01_01_000003_create_permission_tables` |
| Sanctum table tidak ada | Tidak auto-publish | Buat migration `personal_access_tokens` |
| `model_has_roles.team_id` tidak ada | `teams=false` di config | Fix `CheckTenantAccess` middleware tanpa query Spatie teams |
| Isolasi T1+T2 = 200 | `SetTenantFromUser` override header | Hapus `SetTenantFromUser` dari API routes, gunakan `CheckTenantAccess` |
| Login return HTML | ValidationException tanpa `Accept: application/json` | Selalu kirim `Accept: application/json` header |

---

## 12. Langkah Berikutnya (Sprint 3-6)

| Sprint | Goal | Task |
|--------|------|------|
| **S3** | Presensi UI + Rekap | Grid tap-toggle Blade, dashboard kepala madrasah |
| **S4** | WA Gateway Baileys | Multi-session Node.js, queue Laravel, retry 3× |
| **S5** | Keuangan + Portal | Kwitansi PDF, Next.js PWA go-live |
| **S6** | UAT + Go-Live | 4 Acceptance Gate, Docker deploy, invoice cair |

---

*Dev Report ini disusun sesuai dokumentasi 48 sesi ANALISA_KELAYAKAN_SIMSEKOLAH. Implementasi Laravel 11 telah diverifikasi dengan test runtime langsung.*

**Penulis:** AI Agent  
**Tanggal:** 13 Juni 2026  
**Status:** ✅ SPRINT 1-2 LARAVEL 11 COMPLETE — READY FOR SPRINT 3
