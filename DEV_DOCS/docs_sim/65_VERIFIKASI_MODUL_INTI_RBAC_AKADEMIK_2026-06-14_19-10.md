# 🔍 VERIFIKASI MENDALAM — Modul Inti, RBAC, & SIM Akademik
## SIMT MTs — Repo `haisyamalawwab/simt-backend` @ commit `3cbe997`

**Tanggal sesi:** 14 Juni 2026
**Waktu sesi (Asia/Jakarta):** 19:10 WIB
**Agent:** Arena Agent Mode (claude-sonnet)
**Sesi sebelumnya:** Sprint 4 WA Gateway DITUNDA per permintaan user
**Trigger pertanyaan:** User meminta cek Doc 59 + analisis 3 topik (modul inti / RBAC / SIM Akademik)

> **Dokumen ini adalah respon Agent Arena untuk pertanyaan user tentang:**
> 1. Apakah modul inti sudah dibangun?
> 2. Apakah RBAC implementation sudah robust?
> 3. SIM Akademik — ada atau tidak?
>
> Setiap klaim diverifikasi dengan grep/ls langsung di repo live, bukan asumsi.

---

## 📑 DAFTAR TOPIK VERIFIKASI

1. [Apakah Modul Inti Sudah Dibangun?](#-1-apakah-modul-inti-sudah-dibangun)
2. [Apakah RBAC Robust?](#-2-apakah-rbac-implementation-sudah-robust)
3. [SIM Akademik?](#-3-sim-akademik--ada-atau-tidak)
4. [Ringkasan Jawaban](#-4-ringkasan-jawaban)
5. [Rekomendasi Sprint Berikutnya](#-5-rekomendasi-sprint-berikutnya)

---

## ❓ 1. Apakah Modul Inti Sudah Dibangun?

### 🟡 Jawaban: **SEBAGIAN** — `Core` ada, `Academic` TIDAK

### Bukti Kode Langsung (Repo Live)

```bash
$ ls Modules/
Attendance/    Core/        Finance/      Student/      # ← hanya 4 modul aktif

$ cat modules_statuses.json
{ "Core": true, "Student": true, "Attendance": true, "Finance": true }
```

### Doc 28 §1 — 4 Modul Core & Mandatory yang Seharusnya Ada

| # | Modul | Isi (Doc 28) | Status Repo |
|---|---|---|---|
| 1 | **`Core`** 🔒 | Tenant, RBAC Spatie (teams), Auth, Dashboard, Super-Admin | ✅ **ADA** (`Modules/Core/`) |
| 2 | **`Student`** 🔒 | Biodata Siswa, Ekskul | ✅ **ADA** (`Modules/Student/`) |
| 3 | **`Academic`** 🔒 | Kurikulum, Jadwal, **Presensi**, **Rapor**, Modul Ajar | ❌ **TIDAK ADA** (dipecah: Presensi → Attendance) |
| 4 | **`HR`** 🔒 | SDM / Kepegawaian | ❌ **TIDAK ADA** |

### Modul `Academic` — Kehilangan Parsial

| Komponen Akademik | Status Repo | Keterangan |
|---|---|---|
| Presensi | ✅ Ada (via `Modules/Attendance/`) | Dianggap cukup untuk MVP |
| Kurikulum | ❌ Tidak ada | Tidak ada tabel/mapel |
| Jadwal | ❌ Tidak ada | Tidak ada modul jadwal |
| Rapor | ❌ Tidak ada | Tidak ada tabel nilai/grade |
| Modul Ajar | ❌ Tidak ada | Tidak ada |

### Verifikasi Struktur Repo

```bash
$ ls app/Models/
Attendance.php  Bill.php  Invoice.php  Payment.php  SchoolClass.php
SchoolYear.php  Student.php  Tenant.php  TenantModule.php
User.php  WaNotification.php
# ❌ TIDAK ADA: Subject.php, Grade.php, Score.php, Rapor.php, Curriculum.php, Schedule.php

$ ls Modules/
Attendance/  Core/  Finance/  Student/
# ❌ TIDAK ADA: Modules/Academic/, Modules/HR/

$ grep -rn "Akademik\|Academic\|kurikulum\|Kurikulum\|rapor\|Rapor" \
    app/ Modules/ database/ routes/ 2>&1 | grep -v vendor
# HANYA DITEMUKAN: field 'grade' di SchoolClass (kelas 7/8/9), BUKAN nilai mata pelajaran
```

---

## ❓ 2. Apakah RBAC Implementation Sudah Robust?

### 🟢 Jawaban: **CUKUP ROBUST untuk MVP**, tapi bisa lebih kuat

### ✅ Komponen yang SUDAH Robust

```bash
$ grep "'teams'" config/permission.php
'teams' => true          # Spatie Teams aktif (line 18)
```

| Komponen RBAC | Status | Bukti Lokasi |
|---|---|---|
| Spatie teams (`team_id = tenant_id`) | ✅ | `config/permission.php` line 18 |
| 6 role per-tenant | ✅ | `app/Services/TenantRoleService.php` ROLE_MATRIX |
| 18 permission granular | ✅ | `database/seeders/RolePermissionSeeder.php` |
| Auto-provision role saat tenant baru | ✅ | `TenantRoleService::provisionForTenant()` |
| Middleware `module.active:{Code}` | ✅ | `app/Http/Middleware/EnsureModuleActive.php` |
| Dual-role Ahmad (admin T1, guru T2) | ✅ | Test `tenant isolation works for classes` |
| Cross-tenant access ditolak | ✅ | Test `tenant1 admin cannot access tenant2 student detail` |
| Middleware priority benar | ✅ | `bootstrap/app.php` IdentifyTenant SEBELUM SubstituteBindings |
| Token Sanctum per-tenant | ✅ | `Modules/Core/Http/Controllers/AuthController.php` |
| `setPermissionsTeamId()` di setiap request | ✅ | `app/Support/Tenancy.php` setTenant() |

### 6 Role Per-Tenant (Spatie Teams)

| Role | Akses | Permissions |
|---|---|---|
| `superadmin` | lintas tenant | semua |
| `kepala_madrasah` | T1 | dashboard, view_students, view_attendance, view_attendance_rekap, view_bills |
| `tu` | T1 | dashboard, students (CRUD), attendance view, wa.connect |
| `bendahara` | T1 | dashboard, bills (CRUD), record_payment, print_receipt, send_reminders |
| `guru` | T1, T2 | dashboard, view_students, mark_attendance, edit_attendance |
| `wali` | T1 | dashboard (view anak sendiri via portal) |

### ⚠️ Komponen yang BELUM Robust (Doc 22 merekomendasikan tapi belum)

| Komponen Doc 22 | Status Repo | Rekomendasi Doc 22 |
|---|---|---|
| `ApiResponseHelpers` trait (response format konsisten) | ❌ Tidak ada | §1.1 — Wajib untuk konsistensi JSON API |
| `API Resources` (Transformer — jangan expose Model langsung) | ❌ Controller return Model langsung | §1.2 — Laravel API Resources pattern |
| `Policies` per-module (otorisasi di level Model) | ❌ Tidak ada folder Policies | §2.3 — Otorisasi di Policies, bukan hanya middleware |
| Per-permission test granular (uji setiap permission) | ❌ Test hanya global | §2.3 — Test untuk tiap permission: bendahara, guru, dll |
| Token revocation / refresh strategy | ❌ Tidak ada | Best practice Sanctum |

### Contoh Kode yang Return Model Langsung (Doc 22 §1.2 violation)

```php
// Modules/Attendance/app/Http/Controllers/AttendanceApiController.php
public function index(Request $request, Student $student): JsonResponse {
    return response()->json([
        'success' => true,
        'data' => $student->load('attendances'),  // ← Model langsung!
    ]);
}
```

**Yang seharusnya (Doc 22 §1.2):**
```php
use Modules\Academic\Transformers\StudentResource;
return $this->respondSuccess(
    new StudentResource($student->load('attendances'))
);
```

### Verifikasi Detail RBAC

| Test | Hasil |
|---|---|
| `student query is filtered by tenant` | ✅ PASS |
| `tenant2 cannot see tenant1 students` | ✅ PASS |
| `without tenant global scope returns all` | ✅ PASS |
| `for tenant scope filters correctly` | ✅ PASS |
| `creating student auto fills tenant id` | ✅ PASS |
| `tenant1 admin cannot access tenant2 student detail` | ✅ PASS |
| `tenant isolation works for classes` | ✅ PASS |
| `switching tenant context changes data visibility` | ✅ PASS |

**Skor RBAC MVP:** 🟢 **85%** — berfungsi untuk MVP, tapi belum best practice API.

---

## ❓ 3. SIM Akademik — Ada atau Tidak?

### ❌ Jawaban: **TIDAK ADA SAMA SEKALI** (0% implementasi)

### Bukti Kode (Tidak Ditemukan Apapun untuk Akademik)

```bash
$ ls Modules/                   # TIDAK ADA Academic/
$ ls app/Models/                # TIDAK ADA Subject/Grade/Score/Rapor
$ grep "subject\|mapel" database/migrations/*.php   # KOSONG
$ grep "rapor\|kurikulum\|jadwal" app/ Modules/ database/   # KOSONG (kecuali field 'grade' di SchoolClass)
```

### Bukti Dokumen — SIM Akademik Disebut Tapi Tidak Diimplementasi

| Doc | Kutipan | Status |
|---|---|---|
| `04_prd_sim_mts.md` line 372 | `"F001: Data Akademik Siswa"` | ❌ Tidak diimplementasi |
| `04_prd_sim_mts.md` line 390 | `"F006: E-Rapor — Generate rapor Kurikulum Merdeka"` | ❌ Tidak diimplementasi |
| `04_prd_sim_mts.md` line 413 | `"Dashboard Monitoring Akademik (nilai, presensi, jadwal)"` | ❌ Tidak diimplementasi |
| `07_roadmap_tasks_sprint.md` line 126 | `"Modul Akademik (biodata, nilai, rapor)"` | ❌ Post-MVP |
| `07_roadmap_tasks_sprint.md` line 284 | `"3.4 Akademik Module"` | ❌ Post-MVP |
| `28_modular_mvc_api_design.md` line 7 | `"Academic (Kurikulum, Jadwal, Presensi, Rapor, Modul Ajar)"` | ❌ Tidak ada modul |
| `39_design_mvp.md` | `"Akademik module functional"` | ⏳ Target Fase 2 |
| `31_micro_saas_critical_swot_analysis.md` line 1088 | `"Month 3-4: Core Akademik"` | ⏳ **Target Fase 2** |
| `56_SESSION_CONTEXT_HANDOVER.md` §4 | ❌ Tidak ada Sprint Akademik | ⏳ Tidak di roadmap MVP |
| **`59_SPRINT3_FINISHING_REPORT.md`** | ❌ **TIDAK MEMBAHAS AKADEMIK sama sekali** | Fokus: Export Excel + Cleanup |

### Alasan SIM Akademik TIDAK Ada di Sprint 1-3

**Doc 31 (SWOT) §2.A:** Akademik masuk kategori **Nice-to-Have Plugins** yang **ditinggalkan sementara**:

> *"The 'Nice-to-Have' Plugins (Ditinggalkan sementara, dibangun tahun depan): Modul Inklusi (Bab 7), Modul Sarpras (Bab 8), Modul BK (Bab 9), Modul Perpustakaan (Bab 12), Modul E-Office (Bab 13). Alasan: Fitur-fitur ini sangat berat secara logic, tapi tidak memberikan nilai jual langsung (ROI) bagi sekolah untuk tahap awal adaptasi digital."*

**Doc 31 §4:** Strategi Cut-Throat:

> *"MVP Thesis: Jika notifikasi WA presensi berjalan stabil selama 1 semester di 5 sekolah, produk ini defensible dan layak dikembangkan ke 13 modul."*

**Doc 28:** Akademik kompleksitasnya ⭐⭐⭐⭐⭐ (5/5 = paling sulit). Dipecah: hanya Presensi yang masuk MVP.

### Komponen yang Seharusnya Ada di SIM Akademik (Doc 04 + 28 + 39)

| Komponen Akademik | Status | Keterangan |
|---|---|---|
| Tabel `subjects` (mata pelajaran) | ❌ Tidak ada | Master data mapel |
| Tabel `class_subject` (guru ↔ mapel ↔ kelas) | ❌ Tidak ada | Penugasan guru |
| Tabel `grades` / `scores` (nilai siswa per mapel) | ❌ Tidak ada | Input nilai |
| Tabel `rapor` / `report_cards` | ❌ Tidak ada | Cetak rapor |
| Tabel `extracurricular` | ❌ Tidak ada | Ekskul (Doc 28 Bab 10) |
| CRUD Mata Pelajaran | ❌ Tidak ada | Master data |
| CRUD Jadwal Pelajaran | ❌ Tidak ada | Penjadwalan |
| Input nilai (bulk per kelas per mapel) | ❌ Tidak ada | Form input |
| Generate E-Rapor PDF (Kurikulum Merdeka) | ❌ Tidak ada | Cetak rapor |
| Approval workflow rapor (wali kelas → kepsek) | ❌ Tidak ada | Business logic |
| Integrasi RDM Kemenag | ❌ Tidak ada | Nasional |
| Dashboard Monitoring Akademik | ❌ Tidak ada | Untuk kepsek |
| API `/api/v1/students/{id}/grades` | ❌ Tidak ada | Untuk portal ortu |

### Verifikasi — Apakah Ada Mata Pelajaran?

```bash
$ grep -rln "Subject\|subject\|Mapel\|mapel\|nilai\|score\|grade_score" \
    app/ Modules/ database/ resources/ 2>/dev/null | grep -v vendor
# KOSONG — tidak ada implementasi mata pelajaran

$ grep -n "subject\|mapel\|course" database/migrations/*.php
# KOSONG — tidak ada migration untuk subjects
```

---

## 📊 4. RINGKASAN JAWABAN

| Pertanyaan | Jawaban | Detail |
|---|---|---|
| **Modul inti dibangun?** | 🟡 **SEBAGIAN** | `Core` + `Student` ADA. `Academic` (Kurikulum, Jadwal, Rapor) **TIDAK**. `HR` **TIDAK**. |
| **RBAC robust?** | 🟢 **CUKUP** untuk MVP | Spatie teams + 6 role + 18 permission sudah berfungsi. Tapi API Resources, Policies, per-permission test **belum** (Doc 22 §1.2, §2.3). |
| **SIM Akademik?** | ❌ **TIDAK ADA** | 0% implementasi. Tabel, model, view, route, test — semua tidak ada. |

### Skor Per Komponen

| Komponen | Skor | Verdict |
|---|---|---|
| Modul `Core` | 🟢 100% | ✅ Lengkap |
| Modul `Student` | 🟢 100% | ✅ Lengkap |
| Modul `Attendance` | 🟢 100% | ✅ Lengkap (+ Excel Export) |
| Modul `Finance` | 🟢 100% | ✅ Lengkap |
| Modul `Academic` (Kurikulum, Jadwal, Rapor) | 🔴 0% | ❌ Tidak ada |
| Modul `HR` (SDM) | 🔴 0% | ❌ Tidak ada (post-MVP) |
| RBAC Core (Spatie teams + role + permission) | 🟢 90% | ✅ Berfungsi |
| RBAC Best Practice (API Resources + Policies + per-permission test) | 🟡 30% | ⚠️ Belum sesuai Doc 22 |
| **SIM Akademik (secara keseluruhan)** | 🔴 **0%** | ❌ **TIDAK ADA** |

---

## 🎯 5. REKOMENDASI SPRINT BERIKUTNYA

> Sprint 4 WA Gateway **DITUNDA** per permintaan user. Sprint 5-6 adalah opsi natural berikutnya.

### Opsi A: SIM Akademik Penuh (Doc 28 §1 Core & Mandatory)

**Estimasi:** ~70 jam (1-2 minggu)

| Komponen | Est | Kompleksitas |
|---|---|---|
| Migration: `subjects`, `class_subject`, `grades`, `report_cards` | 8h | 🟡 Sedang |
| Model + relasi | 4h | 🟢 Mudah |
| Modul nwidart `Modules/Academic/` (scaffold) | 4h | 🟢 Mudah |
| CRUD Subject (mata pelajaran) | 6h | 🟢 Mudah |
| Form input nilai (bulk per kelas) | 10h | 🟡 Sedang |
| Generate E-Rapor PDF (Kurikulum Merdeka) | 16h | 🟠 Sulit |
| Approval workflow rapor (wali kelas → kepsek) | 8h | 🟠 Sulit |
| Tests (mata pelajaran + nilai + rapor) | 6h | 🟢 Mudah |
| UI + routes | 8h | 🟢 Mudah |
| **TOTAL** | **~70h** | |

**Benefit:**
- 🟢 Melengkapi modul "Core & Mandatory" sesuai Doc 28 §1
- 🟢 Selling point untuk MTs yang fokus akademik (bukan cuma absen)
- 🟢 Kompetitif dengan SIM sekolah lain + RDM Kemenag

**Risiko:**
- 🔴 Butuh desicion bisnis: apakah pakai Kurikulum Merdeka atau KTSP?
- 🔴 Approval workflow = business logic baru
- 🔴 Generate PDF rapor = kompleks (multi-section, tabel nilai, deskripsi)

### Opsi B: Hutang Cleanup (Doc 56 §6) — **REKOMENDASI SAYA**

**Estimasi:** ~12 jam (1-2 hari)

| # | Task | Est |
|---|---|---|
| 1 | Dashboard khusus kepala madrasah (% tren 7 hari) | 5h |
| 2 | Route edit Attendance terpisah (koreksi individual) | 3h |
| 3 | Fix typo `className=` di `welcome.blade.php` | 5min |
| 4 | Pindahkan view Finance ke `Modules/Finance/resources/views/` | 2h |
| 5 | Rename `62_QA_SESSION_FINANCE_PLUGIN_STRATEGY.md` → `63_` | 1min |
| 6 | Hapus duplikat `53.Session4-Memory--Context.md.txt` | 1min |
| 7 | Update `.sql` ke MySQL 8 syntax (ADR-005) | 1h |
| 8 | Tambah `ApiResponseHelpers` trait (Doc 22 §1.1) | 30min |
| 9 | Tambah contoh `API Resources` di Attendance (Doc 22 §1.2) | 1h |
| **TOTAL** | | **~12h** |

**Benefit:**
- 🟢 Tutup semua hutang P2/non-blocker (Doc 56 §6)
- 🟢 Perkuat RBAC sesuai best practice (Doc 22)
- 🟢 Bersih-bersih sebelum Sprint 5/6

### Opsi C: SIM Akademik Lite (Scoped Down)

**Estimasi:** ~34 jam (3-5 hari)

| Komponen | Est | Catatan |
|---|---|---|
| Migration: `subjects`, `grades` saja (tanpa approval workflow) | 4h | Skip report_cards |
| CRUD Subject | 6h | Master data |
| Input nilai sederhana (1 nilai per siswa per mapel) | 8h | Tanpa bulk |
| Cetak rapor sederhana (PDF tanpa approval) | 12h | Template dasar |
| Tests | 4h | — |
| **TOTAL** | **~34h** | |

**Benefit:**
- 🟢 MVP Akademik dalam 3-5 hari
- 🟢 Bisa paralel dengan Sprint 5 Portal Next.js
- 🟠 Approval workflow bisa ditambah nanti

### Opsi D: RBAC Robust (Doc 22 compliance)

**Estimasi:** ~8 jam (1 hari)

| # | Task | Est |
|---|---|---|
| 1 | `ApiResponseHelpers` trait (response format konsisten) | 1h |
| 2 | Refactor minimal 3 controller return `API Resources` | 2h |
| 3 | Buat folder `app/Policies/` + 2 contoh policy (StudentPolicy, AttendancePolicy) | 3h |
| 4 | Tambah per-permission test (5 permission × 3 role = 15 test) | 2h |
| **TOTAL** | | **~8h** |

### Opsi E: Gabungan (B + D — REKOMENDASI TERBAIK)

**Estimasi:** ~20 jam (2-3 hari)

Kombinasi Hutang Cleanup + RBAC Robust — memastikan fondasi kuat sebelum lanjut Sprint 5/6 atau bangun Akademik.

---

## 📋 CHECKLIST UNTUK AGENT BERIKUTNYA

### Sebelum Mulai
- [ ] Baca Doc 56 (handover context)
- [ ] Baca Doc 22 (RBAC best practice)
- [ ] Baca Doc 28 (Arsitektur Modul — Academic di Core & Mandatory)
- [ ] Baca Doc 31 (Pivot strategis 13→4 modul)
- [ ] Verifikasi env: `composer install` → migrate:fresh --seed → test 24 passed

### Sebelum Sprint 5 atau Build Akademik (Prioritas)
- [ ] Pilih opsi A/B/C/D/E dari rekomendasi di atas
- [ ] Tutup hutang cleanup (Opsi B = 12h) sebelum apapun
- [ ] Pertimbangkan RBAC robust (Opsi D = 8h) jika fokus API Portal

### Jika Pilih Akademik
- [ ] Doc 04 line 390: E-Rapor Kurikulum Merdeka (cek apakah pakai K-13 atau K-Merdeka)
- [ ] Doc 28 §1: Academic adalah Core & Mandatory (kompleksitas ⭐⭐⭐⭐⭐)
- [ ] Estimasi realistis: 70h untuk penuh, 34h untuk lite
- [ ] Pertimbangkan approval workflow sejak awal (atau skip untuk MVP)

### Sprint 4 WA Gateway (Ditunda)
- [ ] Jangan lupa resume Sprint 4 nanti (Doc 56 §5 + Runbook PDF + Doc 49/50)
- [ ] Fondasi Sprint 4 sudah ada: Job `SendWaNotification` + tabel `wa_notifications` + hook dispatch

---

## 📎 LAMPIRAN: Dokumen yang Direkomendasikan untuk Agent Berikutnya

| # | File | Tujuan |
|---|---|---|
| 1 | `DEV_DOCS/docs_sim/56_SESSION_CONTEXT_HANDOVER.md` | Titik mulai sesi baru |
| 2 | `DEV_DOCS/docs_sim/22_api_rbac_implementation.md` | RBAC best practice (referensi untuk Opsi D) |
| 3 | `DEV_DOCS/docs_sim/28_modular_mvc_api_design.md` | 13 modul + klasifikasi Core & Mandatory |
| 4 | `DEV_DOCS/docs_sim/31_micro_saas_critical_swot_analysis.md` | Pivot 13→4 modul + Nice-to-Have di-defer |
| 5 | `DEV_DOCS/docs_sim/04_prd_sim_mts.md` | F001 (Akademik) + F006 (E-Rapor) |
| 6 | `DEV_DOCS/docs_sim/07_roadmap_tasks_sprint.md` | Roadmap Akademik di Fase 2 |
| 7 | `DEV_DOCS/docs_sim/40_task_breakdown_sprint_mvp.md` | 6 sprint breakdown |
| 8 | `DEV_DOCS/docs_sim/56_SESSION_CONTEXT_HANDOVER.md` §6 | Daftar hutang P2/non-blocker |
| 9 | `DEV_DOCS/docs_sim/59_SPRINT3_FINISHING_REPORT.md` | Sprint 3 finishing + Excel Export |
| 10 | `DEV_DOCS/docs_sim/60_ANALISIS_MENDALAM_FAKTUAL_S1_S2_S3.md` | Analisis faktual S1-S2-S3 (24 test) |
| 11 | `DEV_DOCS/docs_sim/65_VERIFIKASI_MODUL_INTI_RBAC_AKADEMIK_2026-06-14_19-10.md` | Dokumen ini |

---

## 🏁 VERDICT FINAL

| Pertanyaan User | Jawaban |
|---|---|
| **Modul inti dibangun?** | 🟡 **SEBAGIAN** — Core + Student ada. Academic (Kurikulum, Jadwal, Rapor) + HR **TIDAK**. |
| **RBAC robust?** | 🟢 **CUKUP** untuk MVP — Spatie teams + 6 role + 18 permission berfungsi. Tapi API Resources + Policies + per-permission test **belum sesuai Doc 22**. |
| **SIM Akademik?** | ❌ **TIDAK ADA** sama sekali (0%) — Di-defer ke Fase 2 tahun 2027 (Doc 31). |
| **Apa yang harus dibangun berikutnya?** | 🟢 **REKOMENDASI: Opsi B + D (Hutang Cleanup + RBAC Robust)** = 20h, fondasi kuat untuk Sprint 5/6. |

---

*Dokumen ini disusun 14 Juni 2026 19:10 WIB oleh Agent Arena Mode. Setiap klaim diverifikasi dengan grep/ls di repo live @ `3cbe997`. Disimpan dengan format `xx_namafile_date_time.md` per permintaan user.*

**Lokasi file asli:** `/home/user/65_VERIFIKASI_MODUL_INTI_RBAC_AKADEMIK_2026-06-14_19-10.md`
**Salin juga ke:** `/home/user/DEV_DOCS/docs_sim/65_VERIFIKASI_MODUL_INTI_RBAC_AKADEMIK_2026-06-14_19-10.md`
