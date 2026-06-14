# DEV REPORT — PATCH & VALIDASI SQL MYSQL
## SIMT MTs — Sinkronisasi `simt-backend-mysql-migrate.sql` dengan Kode Hasil Stabilisasi

**Tanggal:** 14 Juni 2026
**Penulis:** Agent Arena (sesi sinkronisasi DB)
**Dokumen terkait:** Doc 54 (Dev Report Stabilisasi), `DATABASE_SCHEMA.md`
**Status:** ✅ SELESAI — `.sql` dipatch, divalidasi import ke MariaDB 11.8 (zero error), Laravel jalan di atasnya.

> **UNTUK AGENT AI BERIKUTNYA:** Dokumen ini mencatat patch terhadap file `simt-backend-mysql-migrate.sql` yang ada di root repo. Baca bersama Doc 54.

---

## 1. KONTEKS & MASALAH

User meng-commit `simt-backend-mysql-migrate.sql` ke repo `main` (commit `5e0fa3a` — "feat: add initial database schema and seed data for attendance tracking"). File ini berisi **skema MySQL + seed data** (dump dari HeidiSQL/phpMyAdmin, format `mysqldump`, 1481 baris, ~114 KB).

**Divergensi ditemukan:** `.sql` di-dump dari skema **versi LAMA** (sebelum perbaikan Doc 54), sehingga TIDAK sinkron dengan kode lokal hasil stabilisasi:

| Aspek | `.sql` user (sebelum patch) | Kode (Doc 54) | Status |
|---|---|---|---|
| Nama tabel kelas | `classes` | `school_classes` | 🔴 konflik reserved word |
| NIS/NISN | `KEY` (index biasa) | `UNIQUE` per-tenant | 🔴 bug integritas data |
| Seed data | lengkap & valid | — | ✅ pertahankan |

**Akar masalah:** hasil perbaikan agent (Doc 54) belum pernah ter-push ke GitHub karena folder `.git` selalu di-exclude dari snapshot workspace sandbox. Sementara `.sql` di-dump dari kode versi lama yang masih ada di remote.

**Keputusan user:** **PATCH** `.sql` yang ada (opsi C) — rename tabel + tambah UNIQUE, **pertahankan seed data**. (Sinkronisasi git diurus belakangan.)

---

## 2. PATCH YANG DITERAPKAN

File: `simt-backend-mysql-migrate.sql` (root repo).

### A. Rename tabel `classes` → `school_classes`
- `DROP TABLE` / `CREATE TABLE` / komentar `-- Dumping ...` / `DELETE FROM` / `INSERT INTO` → `school_classes`.
- Nama index & constraint internal: `classes_*` → `school_classes_*` (FK name harus unik global di MySQL → cegah collision).
- FK target di tabel lain:
  - `attendances.class_id` → `REFERENCES \`school_classes\`` (baris ~43)
  - `class_student.class_id` → `REFERENCES \`school_classes\`` (baris ~347)

### B. NIS/NISN jadi UNIQUE per-tenant
- `KEY \`students_tenant_id_nis_index\` (tenant_id, nis)` → `UNIQUE KEY \`students_tenant_id_nis_unique\``
- `KEY \`students_tenant_id_nisn_index\` (tenant_id, nisn)` → `UNIQUE KEY \`students_tenant_id_nisn_unique\``
- (NULL diperlakukan distinct oleh MySQL → siswa tanpa NIS/NISN tetap boleh banyak.)

### C. Yang TIDAK diubah (sengaja dipertahankan)
- Seluruh **seed data** (INSERT).
- String nama file migrasi pada tabel `migrations`: `'0001_01_01_000012_create_school_years_and_classes_table'` (harus cocok nama file migrasi aktual — JANGAN diubah).
- Tabel pivot `class_student` (nama tabel tetap; hanya FK target-nya yang diarahkan ke `school_classes`).

### Diff inti (sebelum → sesudah)
```
- CONSTRAINT `attendances_class_id_foreign` ... REFERENCES `classes` (`id`)
+ CONSTRAINT `attendances_class_id_foreign` ... REFERENCES `school_classes` (`id`)
- CREATE TABLE IF NOT EXISTS `classes` (
+ CREATE TABLE IF NOT EXISTS `school_classes` (
- CONSTRAINT `class_student_class_id_foreign` ... REFERENCES `classes` (`id`)
+ CONSTRAINT `class_student_class_id_foreign` ... REFERENCES `school_classes` (`id`)
+ UNIQUE KEY `students_tenant_id_nis_unique` (`tenant_id`,`nis`),
+ UNIQUE KEY `students_tenant_id_nisn_unique` (`tenant_id`,`nisn`),
```

---

## 3. VALIDASI (dijalankan nyata, bukan asumsi)

Lingkungan uji dibangun: **MariaDB 11.8.6** + PHP ext **pdo_mysql**.

```bash
sudo mysql -e "CREATE DATABASE simt_backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql simt_backend < simt-backend-mysql-migrate.sql      # EXIT 0, zero error
```

### Hasil verifikasi
| Uji | Hasil |
|---|---|
| Import `.sql` ke MariaDB | ✅ zero error (FK ordering valid) |
| Tabel `school_classes` ada, `classes` tidak ada | ✅ `SHOW TABLES` → school_classes |
| NIS/NISN `NON_UNIQUE=0` | ✅ benar-benar UNIQUE |
| Insert NISN duplikat (tenant sama) | ✅ **DITOLAK** `ERROR 1062 ... students_tenant_id_nisn_unique` |
| FK `attendances`/`class_student` → `school_classes` | ✅ via information_schema.KEY_COLUMN_USAGE |
| Seed data utuh | ✅ 2 tenant · 105 user · 5 school_classes · 106 students · 100 attendances · 100 bills · 6 tenant_modules |
| **Laravel jalan di atas `.sql` ini** | ✅ tenant scoping (T1=4 kelas/100 siswa), `currentClass()`="7A", zero error |

> Catatan: hitungan via SQL mentah = total semua tenant (106 siswa); via Laravel (global scope tenant T1) = 100 siswa. Keduanya konsisten.

---

## 4. CARA PAKAI `.sql` (produksi MySQL)

```bash
# 1. Buat database
mysql -u root -p -e "CREATE DATABASE simt_backend CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# 2. Import skema + seed
mysql -u root -p simt_backend < simt-backend-mysql-migrate.sql

# 3. Arahkan .env Laravel
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1  DB_PORT=3306
#    DB_DATABASE=simt_backend  DB_USERNAME=...  DB_PASSWORD=...

# 4. (opsional) regenerasi .sql dari kode jika migration berubah:
php artisan migrate:fresh --seed   # MySQL
mysqldump -u user -p simt_backend > simt-backend-mysql-migrate.sql
```

> ⚠️ Jika menambah migration BARU di kode, jangan lupa regenerasi `.sql` agar tetap sinkron. `.sql` ini adalah snapshot manual, bukan auto-generated saat deploy.

---

## 5. DAMPAK & FILE BERUBAH SESI INI

| File | Aksi |
|---|---|
| `simt-backend-mysql-migrate.sql` | ✏️ dipatch (school_classes + UNIQUE NISN/NIS), seed dipertahankan |
| `DEV_DOCS/DATABASE_SCHEMA.md` | ✏️ ditambah catatan status patch (§5) |
| `DEV_DOCS/55_dev_report_mysql_sql_patch.md` | 🆕 dokumen ini |

---

## 6. HUTANG SINKRONISASI (PENTING untuk agent/user berikutnya)

Hasil 2 sesi terakhir (Doc 54 stabilisasi + Doc 55 patch SQL) **belum ada di GitHub** karena `.git` di-exclude dari workspace snapshot (tidak bisa commit/push dari sandbox). Yang perlu masuk ke repo saat sinkronisasi:

1. **Fix kode** (Doc 54): `currentTenant`→Tenancy, `school_classes`, NISN UNIQUE, modul Finance terpisah, view siswa/rekap, method `classGrid`, fix cast date presensi.
2. **Test:** `tests/Feature/AttendanceModuleTest.php` (5 test) + perbaikan TenantIsolationTest. Total **23 passed**.
3. **`.sql`:** versi patched (dokumen ini).
4. **Dokumentasi:** README.md (ganti default Laravel) + `DEV_DOCS/{54,55,API_CONTRACT,DATABASE_SCHEMA,ARSITEKTUR_MODUL_CORE_vs_PLUGNPLAY,PANDUAN_BUAT_MODUL_PLUGNPLAY}.md`.

> ⚠️ **Konflik potensial saat merge:** remote `main` masih punya migration `classes` (bukan `school_classes`). Saat sinkronisasi, migration di kode lokal (`school_classes`) HARUS menang, dan `.sql` patched HARUS menggantikan yang di remote — agar kode ↔ DB konsisten.

---

## 7. NEXT
- [ ] User/agent sinkronkan perubahan ke GitHub (lihat §6).
- [ ] Lanjut **Sprint 4 — WA Gateway (Baileys)** (lihat Doc 40 & Doc 54 §9).

---

*Semua validasi dilakukan langsung terhadap MariaDB 11.8 + Laravel 11 pada 14 Juni 2026. `.sql` siap pakai untuk MySQL produksi.*
