# 📋 KILAS PETA FILE (FILE INVENTORY) SESI 9
**Daftar Lengkap Berkas yang Ditangani, Ditambahkan, Diedit, atau Dihapus**  
Tujuan: Mempermudah Pemutakhiran (*Update / Git Patch*) pada Existing Repositori Production/Staging

---

## 🚀 1. REPOSITORI BACKEND LARAVEL (`simt-backend`)
Kondisi Akhir: **40 Tests Passed / 100 Assertions (100% Lulus Sempurna Tanpa Warning)**

| Tindakan | Jalur Berkas (*File Path*) | Rincian Perubahan & Tujuan |
|---|---|---|
| 📝 **DIEDIT** | `simt-backend/phpunit.xml` | Mengubah seksi `<php>` dari MySQL ke **SQLite in-memory** (`DB_CONNECTION=sqlite` & `DB_DATABASE=:memory:`). Ini menumpas *QueryException Connection Refused MySQL* saat menjalankan `php artisan test` di *sandbox*. |
| 📝 **DIEDIT** | `simt-backend/tests/Feature/FinanceModuleTest.php` | Mengonversi seluruh anotasi dokumen usang `/** @test */` menjadi atribut **`#[Test]`** PHPUnit 12 modern serta mengimpor `use PHPUnit\Framework\Attributes\Test;`. Ini membebaskan *test run* dari seluruh *deprecation warnings*. |
| 🗑️ **DIHAPUS**| `simt-backend/tests/Feature/FinanceModuleTest-Pacthed.php` | Menghapus berkas *test* duplikat usang yang melanggar standar *autoloading PSR-4* (sebelumnya memunculkan *skipping warnings* saat eksekusi `composer dump-autoload`). |
| 💡 **BARU** | `simt-backend/database/database.sqlite` | Berkas basis data SQLite lokal yang dipulihkan, dijalankan migrasi 24 tabel, dan diisi *seeders* demo sekolah Al-Hikmah & An-Nur (100+ siswa, 100+ wali). |

---

## 📱 2. REPOSITORI PORTAL FRONTEND NEXT.JS (`simt-portalortu`)
Kondisi Akhir: **PWA & Production Re-Branding Ready (v1.0.0)**

| Tindakan | Jalur Berkas (*File Path*) | Rincian Perubahan & Tujuan |
|---|---|---|
| 📝 **DIEDIT** | `simt-portalortu/src/app/layout.tsx` | Perombakan *Re-Branding* Total: Mengubah judul aplikasi, deskripsi, tautan *OpenGraph*, *twitter card*, *authors*, serta menghapus referensi/logo scaffold usang *Z.AI Code Scaffold* menjadi **SIMT MTs Terpadu PWA**. |
| 📝 **DIEDIT** | `simt-portalortu/package.json` | Menyesuaikan atribut `"name"` menjadi `"simt-portalortu"`, menyematkan deskripsi resmi komersial proyek, serta **menghapus dependensi pihak ketiga usang `"z-ai-web-dev-sdk"`** agar basis kode 100% mandiri. |
| 🔒 **DIAMANKAN**| `simt-portalortu/.gitignore` | Memastikan dan memutakhirkan blokir pelacakan (*ignore tracking*) secara ketat terhadap berkas basis data lokal dev (`db/*.db`, `db/*.db-*`) dan *Process ID* runtime (`*.pid`). |
| 🗑️ **UNTRACKED**| `simt-portalortu/db/custom.db`<br>`simt-portalortu/.zscripts/dev.pid` | Menghapus pelacakan basis data dev SQLite lokal dan PID *runtime* dari *index Git* (`git rm --cached`). Ini menambal resiko galat *merge conflict* dan kebocoran data pengujian ke *production*. |
| 💡 **BARU** | `simt-portalortu/README.md` | Membangun keseluruhan panduan *Markdown* proyek yang menguraikan 5 fitur hibrida utama, struktur arsitektur folder Next.js, instruksi *Quick Start* (`bun run dev`), serta tabel kata sandi demo Orang Tua dan Siswa. |
| 💡 **BARU** | `simt-portalortu/CHANGELOG.md` | Membuat berkas catatan historis rilis (*Changelog*) resmi komersial versi `1.0.0` yang membagi log eksekusi ke dalam kategori *Added*, *Security*, dan *Changed*. |

---

## 📚 3. REPOSITORI DOKUMENTASI PROYEK & PANDUAN KERJA (`DEV_DOCS`)
Kondisi Akhir: **89 Berkas Serah Terima & Blueprint Tersinkronisasi**

| Tindakan | Jalur Berkas (*File Path*) | Rincian Perubahan & Tujuan |
|---|---|---|
| 🔄 **SINKRON**| `DEV_DOCS/*` <br>*(88 Berkas Markdown/HTML/Docx/ZIP)* | Penarikan otomatis seluruh kumpulan arsip dokumen spesifikasi, arsitektur micro-SaaS, rancangan kontrak, serta arsip handover Sesi 1 s/d Sesi 8 dari Google Drive. |
| 💡 **BARU** | `DEV_DOCS/76_SESSION_CONTEXT_HANDOVER_SESI9.md` | **[TITIK MASUK MUTLAK SESI 10]** Dokumen serah terima *agentic* yang mencatat ringkasan perbaikan pengetatan *backend* dan *cleanup portal Next.js* untuk dilanjutkan pada Sesi 10. |

---

## 🛠️ 4. BERKAS BANTU ROTASI SANDBOX (`/home/user/`)
Kondisi Akhir: Berkas utilitas operasional yang persisten di dalam *root workspace*.

| Tindakan | Jalur Berkas (*File Path*) | Rincian Perubahan & Tujuan |
|---|---|---|
| 💡 **BARU** | `/home/user/sync_dev_docs.py` | Skrip Python *multithreaded* berkinerja tinggi yang dikonfigurasi untuk memetakan dan mengunduh seluruh isi folder Google Drive `DEV_DOCS`. |
| 💡 **BARU** | `/home/user/LAPORAN_PENYELESAIAN_SESI9.md` | Berkas salinan eksekutif *Markdown* untuk bahan presentasi serah terima Sesi 9. |
| 💡 **BARU** | `/home/user/LAPORAN_PENYELESAIAN_SESI9.html` | Berkas salinan presentasi interaktif HTML yang dirancang secara profesional dengan dukungan tajuk, *badges*, dan pemformatan *Tailwind CSS*. |

---

## 📋 TIPS KILAS BALIK UNTUK GIT UPDATE

Jika Anda adalah pengembang yang bertugas memperbarui (*merging*) repositori *GitHub* saat ini dengan perubahan di atas, cukup eksekusi 3 langkah ringkas berikut:

```bash
# 1. PENGUATAN DI REPOSITORI simt-backend:
# Salin phpunit.xml dan tests/Feature/FinanceModuleTest.php dari sini
git add phpunit.xml tests/Feature/FinanceModuleTest.php
git rm tests/Feature/FinanceModuleTest-Pacthed.php
git commit -m "test: modernize phpunit to v12 attributes and fix sqlite mapping"

# 2. PEMBERSIHAN DI REPOSITORI simt-portalortu:
# Hapus file untracked dan perbarui konfigurasi Next.js
git rm --cached db/custom.db .zscripts/dev.pid 2>/dev/null
git add .gitignore src/app/layout.tsx package.json README.md CHANGELOG.md
git commit -m "chore: pre-production cleanup and official re-branding to SIMT MTs"
```
