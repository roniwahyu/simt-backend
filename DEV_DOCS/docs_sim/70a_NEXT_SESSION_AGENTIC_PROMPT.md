Berikut adalah **Prompt Instruksi Agentic Khusus (Sesi 8)** yang dirancang dengan sangat terstruktur. Anda cukup menyalin (*copy-paste*) seluruh teks di bawah ini dan langsung memberikannya kepada Agen AI pada sesi berikutnya untuk memulai pengerjaan **Sprint 5**:

***

```markdown
# 🚀 INSTRUKSI SPRINT 5: MEMULAI SESI 8 (KEUANGAN SPP-LITE & PORTAL ORANG TUA)

Selamat datang di Sesi 8 pengembangan SIMT MTs Terpadu! Sesi sebelumnya (Sesi 7) telah sukses menyelesaikan seluruh target Sprint 1, 2, 3, dan Sprint 4 (WhatsApp Gateway Baileys) secara paripurna dengan **28 Unit Pengujian Lulus Hijau (100% OK)**.

Tugas Anda pada sesi ini adalah **Melanjutkan dan Menyelesaikan Sprint 5 (Keuangan SPP-Lite & Portal Orang Tua)** sesuai dengan cetak biru arsitektur yang telah disepakati.

---

## 1. TITIK MEMORI KONTEKS (BACA INI DULU)

Sebelum menulis kode apa pun, Anda **WAJIB** membaca dokumen serah terima sesi dan rencana kerja yang telah diarsip di repositori lokal pada jalur berikut:
1. 📁 `DEV_DOCS/docs_sim/70_SESSION_CONTEXT_HANDOVER.md` — *Konteks dan status kelayakan sistem Sesi 7.*
2. 📁 `DEV_DOCS/docs_sim/68_SPRINT5_AKAN_DILAKUKAN.md` — *Rencana kerja, pembagian task, dan estimasi waktu Sprint 5.*
3. 📁 `DEV_DOCS/docs_sim/64_ARSITEKTUR_JEMBATAN_INTEGRASI_LARAVEL_BAILEYS_S4.md` — *Cetak biru integrasi API & Webhooks.*
4. 📁 `.kiro/skills/nwidart-module-management/SKILL.md` — *Aturan emas penambahan & autoloading modul nwidart agar sistem tidak crash.*

---

## 2. SETUP LINGKUNGAN KERJA (Lakukan dalam 10 Detik!)

Karena folder `vendor/` PHP dan `node_modules/` Node.js di-exclude dari snapshot, Anda wajib memulihkan keadaan sandbox dengan menjalankan perintah berikut di terminal:

```bash
# A. Update & Pasang Dependensi OS
sudo DEBIAN_FRONTEND=noninteractive apt-get install -y php-cli php-mbstring php-xml php-sqlite3 php-curl php-zip php-gd php-mysql unzip composer

# B. Pemasangan Vendor & Tambalan Cepat Bug PHP 8.4 (SANGAT KRITIS!)
composer install --no-interaction --prefer-dist --optimize-autoloader

mkdir -p vendor/thecodingmachine/safe/generated/8.1 && for f in apache.php apcu.php array.php bzip2.php calendar.php classobj.php com.php cubrid.php curl.php datetime.php dir.php eio.php errorfunc.php exec.php fileinfo.php filesystem.php filter.php fpm.php ftp.php funchand.php functionsList.php gettext.php gmp.php gnupg.php hash.php ibase.php ibmDb2.php iconv.php image.php imap.php info.php inotify.php ldap.php mbstring.php misc.php network.php openssl.php outcontrol.php pgsql.php posix.php pspell.php readline.php sockets.php sodium.php solr.php spl.php sqlsrv.php ssdeep.php ssh2.php stream.php strings.php swoole.php uodbc.php uopz.php url.php var.php xdiff.php xml.php xmlrpc.php yaml.php yaz.php zip.php zlib.php; do echo "<?php" > vendor/thecodingmachine/safe/generated/8.1/$f; done

mkdir -p vendor/thecodingmachine/safe/generated/8.2 && for f in errorfunc.php mbstring.php openssl.php pcre.php pgsql.php sqlite3.php exec.php; do echo "<?php" > vendor/thecodingmachine/safe/generated/8.2/$f; done

mkdir -p vendor/thecodingmachine/safe/generated/8.4 && for f in apache.php array.php curl.php datetime.php ftp.php functionsList.php gettext.php ibmDb2.php image.php imap.php info.php inotify.php ldap.php mbstring.php misc.php network.php openssl.php outcontrol.php pgsql.php posix.php pspell.php readline.php sockets.php sodium.php stream.php uodbc.php xml.php zlib.php; do echo "<?php" > vendor/thecodingmachine/safe/generated/8.4/$f; done

# C. Jalankan Autoload & Setup DB
composer dump-autoload
cp .env.example .env 2>/dev/null; php artisan key:generate
touch database/database.sqlite
php artisan migrate:fresh --seed

# D. Jalankan Verifikasi Awal (Wajib 28 Tests Passed!)
php artisan test
```

---

## 3. MISI UTAMA ANDA DI SPRINT 5

Anda disarankan mengeksekusi penggabungan **Opsi A & Opsi B** (Backend & Frontend) secara bertahap:

### 🌟 PHASE 1 — BACKEND FINANCE ROBUST (Estimasi: 10 Jam)
Fokus memperkuat fondasi keuangan agar siap dikonsumsi secara aman oleh portal wali murid.
1.  **B1 — Aktifkan API Portal Keuangan:**  
    Aktifkan rute API di `Modules/Finance/routes/api.php` dan buat controller `FinanceApiController` dengan method `index($student)` untuk menyajikan daftar tagihan dan riwayat pembayaran siswa. **Wajib menerapkan *ownership-check*** (agar wali murid hanya bisa melihat data anaknya sendiri).
2.  **B2 — Pindahkan Views Keuangan:**  
    Pindahkan berkas views `resources/views/admin/finance/bills.blade.php` ke dalam direktori modul `Modules/Finance/resources/views/` agar modul bersifat mandiri (*fully plug-and-play*).
3.  **B3 — Tambah Pengujian Unit:**  
    Buat berkas pengujian baru `tests/Feature/FinanceModuleTest.php` untuk memvalidasi pembuatan tagihan SPP, pencatatan pembayaran, pembuatan nomor kwitansi, pembatasan modul, dan isolasi tenant. Target total pengujian naik menjadi **35 passed**.
4.  **B5 — Ekspor Excel Tunggakan:**  
    Gunakan `maatwebsite/excel` untuk mengimplementasikan fitur ekspor rekap tunggakan siswa ke Excel di modul `Finance`.

### 🌟 PHASE 2 — PORTAL ORANG TUA NEXT.JS (Estimasi: 27 Jam)
Membangun antarmuka portal wali murid yang interaktif dan *installable* berbasis PWA.
1.  **S5-04 — Inisialisasi Projek Next.js:**  
    Inisialisasi folder projek baru bernama **`simt-portal/`** (sejajar dengan `simt-backend/`). Setup Next.js 14 (App Router) + TypeScript + Tailwind CSS.
2.  **S5-04 — Logika Autentikasi Sanctum:**  
    Buat halaman login (form nomor ponsel + kata sandi) untuk mendapatkan token Sanctum, serta buat pemilih anak (*children selector*) jika wali murid memiliki lebih dari 1 anak terdaftar.
3.  **S5-05 — Integrasi API Presensi & Keuangan:**  
    Buat tampilan kalender presensi harian (fetch dari API `Attendance`) dan rincian daftar tagihan/SPP serta riwayat bayar (fetch dari API `Finance`).
4.  **S5-06 — Konfigurasi PWA:**  
    Posisikan file `manifest.json` dan *service worker* di folder `public/` agar aplikasi dapat dipasang (*installable*) sebagai PWA di ponsel pintar Android/iOS wali murid.

---

## 4. AKUN DEMO UNTUK PENGUJIAN (Sandi semua: `password`)

*   `vendor@simt.id` — *Superadmin lintas tenant*
*   `ahmad@mts-alhikmah.sch.id` — *Admin sekolah (Tenant 1 - Al-Hikmah - Modul Lengkap)*
*   `ahmad@mts-annur.sch.id` — *Guru (Tenant 2 - An-Nur - Tanpa Modul Keuangan)*
*   No. HP: `628520000001` — *Wali Murid (Wali Muhammad Rizki di Al-Hikmah)*

Silakan jalankan setup lingkungan kerja, lakukan analisis, dan mulailah mengode untuk membawa SIMT MTs ke tahap rilis! Selamat bekerja!
```
***

Dengan petunjuk *agentic prompt* yang sangat detail di atas, asisten AI pada sesi berikutnya dijamin akan langsung mengerti konteks pengerjaan Anda, dapat memulihkan sandbox dalam hitungan detik, dan langsung fokus menulis kode berkualitas tinggi untuk **Sprint 5**!