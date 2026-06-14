# KONTRAK API & ROUTE — SIMT MTs
## Referensi Endpoint (Web Blade + REST API) — diverifikasi dari `route:list`

**Tanggal:** 14 Juni 2026 · **Total route:** 52 · **Base API:** `/api/v1`
**Header wajib (API):** `Authorization: Bearer {token}` + `X-Tenant-Domain: {domain}` + `Accept: application/json`

> Sumber: hasil `php artisan route:list` pada repo `main`. Endpoint web dilindungi session + RBAC; endpoint API dilindungi Sanctum + `check.tenant.access` + `module.active`.

---

## 1. AUTENTIKASI & CORE (Modul Core — selalu aktif)

### REST API
| Method | Endpoint | Auth | Deskripsi |
|---|---|---|---|
| POST | `/api/v1/auth/login` | publik | Login (email **atau** phone + password) → token Sanctum 30 hari |
| GET | `/api/v1/ping` | header tenant | Cek tenant aktif (health + identitas tenant) |
| GET | `/api/v1/me` | Bearer | Profil user + tenant |
| GET | `/api/v1/me/children` | Bearer (wali) | Daftar anak milik wali |
| POST | `/api/v1/logout` | Bearer | Hapus token aktif |
| GET | `/api/health` | publik | Health check service |

**Contoh login (200):**
```json
// Request
{ "login": "ahmad@mts-alhikmah.sch.id", "password": "password" }
// Response
{ "success": true, "data": { "user": {...}, "token": "1|xxxx..." } }
```
**Error standar:** `400 TENANT_NOT_FOUND` · `402 TENANT_SUSPENDED` · `403 FORBIDDEN_TENANT` / `MODULE_INACTIVE` · `422` validasi.

### Web (Blade, session)
| Method | URI | Name | Akses |
|---|---|---|---|
| GET | `/login` | `login` | publik |
| POST | `/login` | — | publik (webLogin) |
| POST | `/logout` | `logout` | auth |
| GET | `/dashboard` | `dashboard` | auth + tenant |
| GET | `/admin` | `super.dashboard` | superadmin |
| GET/POST | `/admin/tenants/create`, `/admin/tenants` | `super.tenant.*` | superadmin |
| PUT | `/admin/tenants/{tenant}` | `super.tenant.update` | superadmin |

---

## 2. KESISWAAN (Modul Student — plug & play)

### Web
| Method | URI | Name | Permission |
|---|---|---|---|
| GET | `/students` | `students.index` | `view_students` |
| GET | `/students/create` | `students.create` | `create_students` |
| POST | `/students` | `students.store` | `create_students` |
| GET | `/students/{student}/edit` | `students.edit` | `edit_students` |
| PUT | `/students/{student}` | `students.update` | `edit_students` |
| DELETE | `/students/{student}` | `students.destroy` | `delete_students` |
| GET | `/students/import` | `students.import.form` | `import_students` |
| POST | `/students/import/upload` | `students.import.upload` | wizard step 1-2 |
| POST | `/students/import/commit` | `students.import.commit` | wizard step 3 |

### REST API
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/v1/students` | List siswa (paginated, gated `module.active:Student`) |
| GET | `/api/v1/students/{student}` | Detail siswa |

---

## 3. PRESENSI (Modul Attendance — plug & play)

### Web
| Method | URI | Name | Deskripsi |
|---|---|---|---|
| GET | `/attendance` | `attendance.index` | Grid presensi (pilih kelas + tanggal) |
| GET | `/attendance/class/{class}` | `attendance.grid` | Grid presensi kelas tertentu |
| POST | `/attendance` | `attendance.store` | Simpan bulk (JSON: class_id, date, records[]) |
| GET | `/attendance/rekap` | `attendance.rekap` | Rekap bulanan (class_id, month=Y-m) |

**Payload `attendance.store`:**
```json
{ "class_id": 1, "date": "2026-06-14",
  "records": [ { "student_id": 10, "status": "A" } ] }
// status: H=Hadir, A=Alpa, I=Izin, S=Sakit, T=Terlambat
```

### REST API (Portal Ortu)
| Method | Endpoint | Deskripsi |
|---|---|---|
| GET | `/api/v1/students/{student}/attendances?month=Y-m` | Riwayat presensi anak |

---

## 4. KEUANGAN (Modul Finance — plug & play)

### Web
| Method | URI | Name | Deskripsi |
|---|---|---|---|
| GET | `/finance/bills` | `finance.bills` | Daftar tagihan (filter siswa/status/periode) |
| POST | `/finance/bills/generate` | `finance.bills.generate` | Generate tagihan massal |
| POST | `/bills/{bill}/payment` | `finance.payment.store` | Catat pembayaran (parsial OK) |
| GET | `/payments/{payment}/receipt` | `finance.receipt` | Kwitansi PDF (`KW/{tenant}/{tahun}/{seq}`) |
| POST | `/finance/reminders` | `finance.reminders` | Antri pengingat WA |

### REST API
- `GET /api/v1/students/{student}/bills` — **placeholder** (implementasi Sprint 5, Portal Ortu).

---

## 5. RBAC — Role & Permission

**6 role per-tenant (Spatie teams):** `superadmin`, `kepala_madrasah`, `tu`, `bendahara`, `guru`, `wali`.

**Permission utama:** `view_dashboard`, `manage_tenants`, `manage_users`, `manage_roles`, `view_students`, `create_students`, `edit_students`, `delete_students`, `import_students`, `mark_attendance`, `view_attendance`, `edit_attendance`, `view_attendance_rekap`, `view_bills`, `create_bills`, `record_payment`.

---

## 6. Middleware Pipeline

| Alias | Kelas | Fungsi |
|---|---|---|
| `identify.tenant` | `IdentifyTenant` | Tenant dari header `X-Tenant-Domain` / subdomain (API) |
| `set.tenant.user` | `SetTenantFromUser` | Tenant dari `users.tenant_id` (web session) |
| `check.tenant.access` | `CheckTenantAccess` | Token Sanctum harus cocok tenant |
| `module.active` | `EnsureModuleActive` | Cek langganan `tenant_modules` → 403 `MODULE_INACTIVE` |
| `role` / `permission` | Spatie | RBAC |

> **Penting:** `IdentifyTenant`/`SetTenantFromUser` berjalan **sebelum** `SubstituteBindings` (di-set via middleware priority `bootstrap/app.php`) agar route-model-binding tidak bocor lintas tenant.

---

*Kontrak ini selaras dengan Doc 17/29/39. Update bila menambah endpoint modul baru.*
