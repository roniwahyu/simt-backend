# SKEMA DATABASE — SIMT MTs
## 12 Tabel Domain + Tabel Sistem — diverifikasi dari `PRAGMA table_info`

**Tanggal:** 14 Juni 2026 · **DB dev/test:** SQLite · **DB produksi:** MySQL 8
**Migrasi:** 11 file di `database/migrations/` · `migrate:fresh` terverifikasi sukses.

> ⚠️ **Catatan MySQL:** Tabel kelas dinamai **`school_classes`** (bukan `classes`) untuk menghindari reserved word di MySQL/PostgreSQL produksi. User akan meng-update `.sql` dari migration MySQL — pastikan nama tabel ini konsisten.

---

## 1. ATURAN GLOBAL

1. **SEMUA tabel domain wajib `tenant_id`** (FK ke `tenants`, `cascadeOnDelete`) + trait `BelongsToTenant` (global scope + auto-fill).
2. **Composite index** dengan kolom query utama, mis. `(tenant_id, date)` pada `attendances`.
3. `users` **TIDAK** pakai global scope tenant (auth terjadi sebelum konteks tenant).
4. Money: `numeric/decimal`. Tanggal presensi: cast `date:Y-m-d`.

---

## 2. RELASI (ERD ringkas)

```
tenants 1──* tenant_modules            (langganan modul: module_code, active, active_until)
tenants 1──* invoices                  (tagihan vendor→sekolah, prepaid semester)
tenants 1──* users                     (admin/guru/wali; phone unik; role_display)
tenants 1──* school_years 1──* school_classes (wali kelas = teacher_id→users)
school_classes *──* students           (via class_student, per school_year)
users(wali) *──* students              (via guardian_student, relation)
students 1──* attendances              (UNIQUE student_id+date; marked_by→users)
students 1──* bills 1──* payments      (payments.receipt_no unik; recorded_by→users)
tenants 1──* wa_notifications          (antrian WA: to_phone, type, payload, status, attempts)

Spatie (teams=true): roles(team_id=tenant_id), permissions, model_has_roles, ...
```

---

## 3. DETAIL KOLOM (tabel domain)

### `tenants` — master sekolah
`id` · `name` · `domain` (unik) · `phone` · `address` · `status` (state machine: prospect→contracted→active→grace_read→suspended→terminated) · `activated_at` · `grace_until` · `settings` (JSON, mis. `wa_notify_hadir`) · timestamps

### `tenant_modules` — langganan modul (plug & play lapisan komersial)
`id` · `tenant_id` FK · `module_code` (Core/Student/Attendance/Finance/...) · `active` (bool) · `active_until` · timestamps
> `Tenant::hasModule($code)` = ada baris dgn `active=true`.

### `users`
`id` · `tenant_id` FK (nullable utk superadmin) · `name` · `email` (nullable) · `phone` (NOT NULL, login wali) · `password` · `role_display` · `is_active` · `last_login_at` · `remember_token` · timestamps

### `school_years`
`id` · `tenant_id` FK · `name` (mis. 2026/2027) · `start_date` · `end_date` · `is_active` · timestamps · **UNIQUE(tenant_id, name)**

### `school_classes` *(⚠️ bukan `classes`)*
`id` · `tenant_id` FK · `school_year_id` FK · `name` (mis. 7A) · `grade` · `teacher_id` FK→users (wali kelas) · timestamps · **UNIQUE(tenant_id, school_year_id, name)**

### `students`
`id` · `tenant_id` FK · `nis` · `nisn` · `name` · `gender` (L/P) · `birth_date` · `birth_place` · `address` · `status` (active/inactive/graduated/transferred) · timestamps
> **UNIQUE(tenant_id, nis)** & **UNIQUE(tenant_id, nisn)** — NULL distinct (siswa tanpa NIS tetap boleh banyak).

### `class_student` (pivot)
`id` · `student_id` FK · `class_id` FK→school_classes · `school_year_id` FK · timestamps · **UNIQUE(student_id, class_id, school_year_id)**

### `guardian_student` (pivot)
`id` · `user_id` FK (wali) · `student_id` FK · `relation` (ayah/ibu/wali) · timestamps · **UNIQUE(user_id, student_id)**

### `attendances`
`id` · `tenant_id` FK · `student_id` FK · `class_id` FK→school_classes · `date` · `status` (H/A/I/S/T) · `arrival_time` · `notes` · `marked_by` FK→users (audit) · timestamps
> **UNIQUE(student_id, date)** · index `(tenant_id, date)` & `(tenant_id, class_id, date)`

### `bills`
`id` · `tenant_id` FK · `student_id` FK · `period` (YYYY-MM) · `component` · `amount` · `paid_amount` · `discount` · `status` (unpaid/partial/paid) · `due_date` · timestamps

### `payments`
`id` · `tenant_id` FK · `bill_id` FK · `student_id` FK · `amount` · `payment_date` · `method` (cash/transfer) · `reference` · `receipt_no` (unik, `KW/{tenant}/{tahun}/{seq}`) · `recorded_by` FK→users · `notes` · timestamps

### `invoices` — tagihan vendor→sekolah
`id` · `tenant_id` FK · `period` · `amount` · `status` · `paid_at` · `payment_method` · timestamps

### `wa_notifications` — antrian WhatsApp (Sprint 4)
`id` · `tenant_id` FK · `to_phone` · `type` (attendance/bill_reminder/credentials) · `payload` (JSON) · `status` (pending/sent/failed) · `attempts` · `last_error` · `sent_at` · timestamps

---

## 4. TABEL SISTEM (Laravel/Spatie/Sanctum)
`migrations`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`, `sessions`, `password_reset_tokens`, `personal_access_tokens` (Sanctum), `roles`, `permissions`, `model_has_roles`, `model_has_permissions`, `role_has_permissions` (Spatie).

---

## 5. CATATAN MIGRASI MYSQL (untuk update .sql)

> **STATUS (14 Juni 2026):** File `simt-backend-mysql-migrate.sql` di root repo SUDAH dipatch & divalidasi import ke MariaDB 11.8 (zero error). Patch yang diterapkan terhadap versi awal user:
> 1. Tabel `classes` → **`school_classes`** (+ semua FK di `attendances` & `class_student` + index/constraint name internal).
> 2. `KEY students_tenant_id_nis(n)_index` → **`UNIQUE KEY students_tenant_id_nis(n)_unique`** (integritas NISN/NIS per-tenant; diuji: insert duplikat → `ERROR 1062`).
> 3. Seed data user (2 tenant, 105 user, 5 kelas, 106 siswa, 100 presensi, 100 tagihan, 6 tenant_modules termasuk Finance) **dipertahankan utuh**.
> Verifikasi: Laravel (model `SchoolClass`→`school_classes`, Tenancy global scope) berjalan di atas `.sql` ini → tenant scoping & `currentClass()` OK.


1. **Nama tabel kelas = `school_classes`** (konsisten di FK `class_student.class_id`, `attendances.class_id`).
2. Money pakai `DECIMAL(12,2)` di MySQL (migration `numeric`).
3. UNIQUE NISN/NIS per-tenant: di MySQL, NULL juga distinct pada unique index → aman.
4. `settings`, `payload` = kolom JSON/TEXT.
5. `attendances.date` = `DATE`; query rekap pakai `whereBetween` (portable), **bukan** `DATE_FORMAT` (MySQL-only) — sudah diperbaiki.
6. Charset disarankan `utf8mb4_unicode_ci`.

Generate `.sql` dari migration:
```bash
# Dump skema MySQL setelah migrate
mysqldump -u user -p --no-data simt_db > database/schema/mysql-schema.sql
# atau pakai schema:dump Laravel
php artisan schema:dump
```

---

*Selaras dengan ERD Doc 06 & 39. Update bila ada migration baru.*
