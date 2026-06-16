# LAPORAN ANALISIS PROFESOR IT & SENIOR ENGINEER
## Proyek: SIMT MTs/Yayasan (Sistem Informasi Manajemen Terpadu)
**Tanggal:** 16 Juni 2026  
**Status:** Audit Strategis & Teknis Mendalam

---

## BAGIAN 1: ANALISIS STRATEGIS, BISNIS, DAN DOKUMENTASI (DRIVE)

### 1. Analisis Market Fit: Malang & Indonesia
Secara strategis, produk ini memiliki **Market Fit yang sangat kuat**, terutama di wilayah Malang Raya yang memiliki kepadatan Madrasah (MTs) swasta di bawah yayasan yang sangat tinggi.

*   **Penyelesaian "Pain Point" Utama:** SIMT masuk ke "pelaporan ke samping" (orang tua) melalui **WA Gateway**. Orang tua di Indonesia lebih peduli notifikasi WA real-time tentang kehadiran anak daripada dashboard web yang rumit.
*   **Strategi Integrasi:** Keputusan untuk menggunakan **Import Excel** dari hasil ekspor DAPODIK/EMIS di tahap MVP adalah langkah sangat tepat untuk menghindari birokrasi API kementerian.
*   **Unique Selling Point (USP):** Fitur **Tahfiz** dan **Inklusi/PDBK** adalah magnet kuat bagi Madrasah yang saat ini sedang gencar mempromosikan program unggulan untuk bersaing dengan sekolah negeri.

### 2. Kelayakan MVP: 3 Bulan & Modal 5 Juta
Berdasarkan dokumen PRD MVP (Doc 37), target ini **Bisa Dicapai** dengan syarat:
*   **Anggaran:** 5 juta IDR mencukupi hanya untuk infrastruktur (VPS, Domain, AI Tools, Marketing). Asumsi biaya pengembang (founder) adalah 0.
*   **Timeline:** Sangat agresif. Kunci keberhasilannya ada pada **Pembersihan Arsitektur** (Doc 76). Anda harus segera menghapus modul-modul redundan.
*   **Rekomendasi:** Gunakan infrastruktur efisien (HestiaCP/CyberPanel) dan pisahkan instance WA Gateway agar tidak mengganggu performa API.

### 3. Review Dokumentasi & Visualisasi
*   **Design ERD (Doc 06):** Struktur tabel sudah menggunakan UUID dan `tenant_id`. Namun, terjadi konflik istilah antara `school_classes` dan `classrooms` yang harus segera disatukan.
*   **Diagram Proses (Doc 42):** Alur bisnis "TTD MoU -> Bayar -> Aktif" adalah proteksi bisnis yang sangat senior untuk menjaga *cash flow*.
*   **Sesi 9 (Doc 76):** Menunjukkan risiko "Technical Debt". Pembersihan harus menjadi prioritas sebelum masuk ke fitur Finance.

---

## BAGIAN 2: ANALISIS TEKNIS MENDALAM (CODEBASE SIMT-BACKEND)

### 1. Inkonsistensi Arsitektur Modular (Technical Debt)
*   **Struktur Folder Ganda:** Terjadi tabrakan standar antara modul lama (langsung `Http/`) dan modul baru (sub-folder `app/`). Ini berisiko pada kegagalan *class loading* di lingkungan Linux.
*   **Massive Scaffold:** Banyak modul hasil *generate* otomatis (`Akademik`, `Dapur`, `Kantin`, dll) yang memiliki `module.json` tidak valid karena mereferensikan Provider yang tidak ada.

### 2. Analisis Implementasi Multi-Tenancy
*   **Isolasi Data:** Penggunaan `Global Scope` via `BelongsToTenant` sudah tepat dan elegan.
*   **Celah Keamanan:** Middleware `IdentifyTenant.php` hanya mengandalkan header `X-Tenant-Domain`. Ada risiko **Cross-Tenant Data Leakage** jika user mengubah header secara manual. Perlu validasi tambahan: `user->tenant_id === current_tenant->id`.

### 3. "Migration Nightmare" (Konflik Database)
*   **Duplikasi Tabel:** Terdapat file migrasi core dan migrasi "liar" yang menabrak tabel yang sama (seperti `tenants` dan `users`).
*   **Inkonsistensi Relasi:** Tabel `school_classes` vs `classrooms`. Menjalankan migrasi sekarang akan memicu error fatal atau kerusakan relasi Eloquent.

### 4. Logic Controller & Service Layer
*   **Fat Controller:** Logika bisnis masih menumpuk di Controller. Disarankan mulai menggunakan **Service Layer** untuk menangani logika kompleks seperti presensi yang memicu notifikasi WA.
*   **WA Gateway:** Sudah menggunakan Job/Queue (`SendWaNotification.php`), namun belum ada mekanisme **Rate Limiting** per tenant untuk mencegah nomor WA di-ban oleh WhatsApp.

### 5. Integrasi Frontend (Next.js)
*   **Authentication:** Penggunaan Laravel Sanctum sudah tepat.
*   **Stateful Domain:** Perlu dipastikan konfigurasi `SESSION_DOMAIN` dinamis untuk menangani multi-subdomain pada portal orang tua.

---

## RENCANA TINDAKAN DARURAT (ACTION PLAN)

1.  **Refactoring Migrasi (High Priority):** Hapus 11 migrasi untracked yang duplikat dan integrasikan kolom fitur baru (`subjects`, `grades`) ke migrasi core.
2.  **Standardisasi Modul:** Gunakan standar folder `app/` di dalam modul dan hapus modul non-MVP (Dapur, Kantin, dll).
3.  **Keamanan Tenancy:** Update middleware untuk memverifikasi `tenant_id` user yang sedang login terhadap tenant aktif.
4.  **Scope Lock:** Hentikan penambahan fitur baru sebelum struktur arsitektur dibersihkan (sesuai mandat Doc 76).

**Diagnosa Akhir:** Fondasi Tenancy kuat, namun struktur folder dan migrasi sangat rapuh. **Status: YELLOW LIGHT (Perbaiki Arsitektur Sebelum Lanjut).**
