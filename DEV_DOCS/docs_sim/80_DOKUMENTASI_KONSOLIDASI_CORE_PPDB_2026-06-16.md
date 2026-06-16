# 📑 LAPORAN KONSOLIDASI ARSITEKTUR: CORE AKADEMIK & PPDB
**Tanggal:** 16 Juni 2026  
**Status:** IMPLEMENTED  
**Prioritas:** High (Production Ready)

---

## 1. STRATEGI KONSOLIDASI
Untuk mencapai target MVP 3 bulan dengan modal Rp 5.000.000, arsitektur sistem telah dirampingkan. Fokus utama adalah pada **Stabilitas Core** dan **Fitur Killer (WA Notif)**, sementara modul pendukung dialihkan menjadi "Embrio" (ditunda).

### Perubahan Utama:
1.  **Standardisasi Folder:** Semua modul menggunakan sub-folder `app/` untuk logika PHP sesuai standar Laravel 11.
2.  **Mapping PSR-4:** Pembaruan `composer.json` untuk otomatisasi loading class modular.
3.  **Migration Cleanup:** Penghapusan 11 migrasi "liar" yang duplikat untuk mencegah kerusakan database saat deployment.
4.  **Shelving Modul:** Menonaktifkan modul non-inti melalui `module.json` tanpa menghapus source code.

---

## 2. DAFTAR STATUS MODUL & FILE (KEEP vs DELETE/POSTPONE)

Berikut adalah tabel klasifikasi komponen sistem setelah proses audit:

| Komponen | Nama / Modul | Status | Alasan / Tindakan |
| :--- | :--- | :--- | :--- |
| **Modul Inti** | `Core` | **KEEP** | Jantung sistem (Auth, Tenancy, RBAC). |
| **Modul Inti** | `Student` | **KEEP** | Master data siswa (Integrasi Excel). |
| **Modul Inti** | `Attendance` | **KEEP** | Killer Feature (Presensi + WA Notif). |
| **Modul Inti** | `Finance` | **KEEP** | Keuangan Lite (Tagihan SPP). |
| **Modul Inti** | `Notification` | **KEEP** | Mesin pengiriman WA & In-App. |
| **Modul Inti** | `Akademik` | **KEEP** | Core Akademik (Rombel, Mapel, Nilai). |
| **Modul Inti** | `Ppdb` | **KEEP** | Penerimaan Siswa Baru (Production Ready). |
| **Modul Redundan** | `Keuangan` | **DELETE** | Duplikat dengan modul `Finance`. |
| **Modul Redundan** | `Presensi` | **DELETE** | Duplikat dengan modul `Attendance`. |
| **Modul Non-MVP** | `Dapur`, `Kantin` | **DELETE** | Di luar scope MVP 3 Bulan. |
| **Modul Embrio** | `Alumni` | **POSTPONE** | Nonaktif (Priority 0, Active: false). |
| **Modul Embrio** | `Berita` | **POSTPONE** | Nonaktif (Priority 0, Active: false). |
| **Modul Embrio** | `Perpustakaan` | **POSTPONE** | Nonaktif (Priority 0, Active: false). |
| **Modul Embrio** | `Persuratan` | **POSTPONE** | Nonaktif (Priority 0, Active: false). |
| **Modul Embrio** | `Pendaftaran` | **POSTPONE** | Nonaktif (Fungsi digantikan oleh `Ppdb`). |
| **Migration** | `0001_` s.d `0004_` | **DELETE** | Duplikat dengan migrasi Core Set A. |
| **Migration** | `0005_`, `0008_` | **KEEP** | Fitur baru (Subjects & Grades). |
| **Migration** | `Set A (Core)` | **KEEP** | Fondasi database yang stabil. |

---

## 3. STANDAR PENAMAAN (NAMING CONVENTION)
Untuk sinkronisasi dengan DAPODIK, EMIS, dan RDM, standar berikut wajib diikuti:

| Entitas | Code / Model | Database Table | Context |
| :--- | :--- | :--- | :--- |
| **Tahun Pelajaran** | `SchoolYear` | `school_years` | Standard DAPODIK |
| **Rombel / Kelas** | `SchoolClass` | `school_classes` | Standard EMIS |
| **Mata Pelajaran** | `Subject` | `subjects` | Standard RDM |
| **Siswa** | `Student` | `students` | Master Data |
| **Nilai** | `Grade` | `grades` | Akademik |
| **Tenant / Madrasah** | `Tenant` | `tenants` | Multi-tenancy |

---

## 4. NEXT STEP: ACADEMIC SEEDING
Langkah selanjutnya adalah mengisi modul `Akademik` dengan data standar MTs:
1.  **Daftar Mapel:** (Quran Hadits, Akidah Akhlak, Fiqih, SKI, PKn, B. Indo, B. Arab, MTK, IPA, IPS, B. Inggris).
2.  **Struktur Rombel:** (Kelas 7-A, 7-B, 8-A, dst).
3.  **PPDB Flow:** Setup formulir pendaftaran awal.

---
*Dokumen ini dibuat oleh Profesor IT / Senior Engineer SIMT.*
