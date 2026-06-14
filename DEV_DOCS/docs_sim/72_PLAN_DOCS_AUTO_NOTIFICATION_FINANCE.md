# 📋 PLAN DOCS — Auto-Notifikasi WhatsApp & Manual Reminder Tagihan Siswa

**Tanggal:** 14 Juni 2026  
**Status:** 📝 Rencana Implementasi & Dokumentasi Pengembang  
**Modul Utama:** `Modules\Finance` (Keuangan-Lite) & `Modules\Notification` (WhatsApp Gateway Integration)  

---

## 🎯 Latar Belakang & Kebutuhan Fitur
Untuk mempercepat penagihan SPP/tagihan sekolah, sistem SIMT memerlukan:
1. **Auto-Notifikasi saat Pembuatan**: Saat bendahara men-generate tagihan massal untuk satu periode (misal: SPP Juni 2026), notifikasi WhatsApp pengingat tagihan harus dapat langsung dikirimkan ke nomor WhatsApp orang tua (wali) masing-masing siswa secara otomatis jika opsi ini diaktifkan.
2. **Kirim Pengingat Manual (Per Siswa)**: Menambahkan tombol "Kirim Notif" di setiap baris tagihan siswa di dashboard admin agar admin dapat mengirim ulang pesan pengingat tagihan secara personal secara instan kapan saja.

---

## ⚙️ Detail Arsitektur & Implementasi Teknik

### 1. Integrasi dengan Modul WhatsApp
Pengiriman WhatsApp akan memanfaatkan job antrean Laravel yang sudah ada:
- **Job**: `App\Jobs\SendWaNotification`
- **Tipe Pesan**: `bill_reminder`
- **Struktur Payload**:
  - `student_name`: Nama siswa yang bersangkutan.
  - `component`: Komponen tagihan (misal: "SPP").
  - `period`: Periode tagihan format YYYY-MM.
  - `amount`: Jumlah tagihan tersisa (menggunakan `$bill->remaining()`).
- **Nomor Tujuan**: Dikirim ke semua nomor HP wali (`phone` di model `guardian` / `User`) yang terasosiasi dengan siswa tersebut.

### 2. Auto-Notifikasi via Modal Generate Tagihan Massal
Pada modal "Generate Tagihan Massal", ditambahkan input checkbox:
- **Label**: `Kirim Notifikasi Otomatis ke WhatsApp Orangtua`
- **Parameter Request**: `auto_notify` (boolean)
- **Logika di Controller (`generateBills`)**:
  - Setelah `Bill::create` berhasil dilakukan untuk setiap siswa aktif, cek apakah `auto_notify` bernilai `true`.
  - Jika ya, ambil relasi wali (`guardians`) dari siswa tersebut, lalu lakukan dispatch job `SendWaNotification` ke antrean `wa`.

### 3. Tombol Kirim Notifikasi Manual per Baris Siswa
Pada tabel tagihan di `bills.blade.php`:
- Untuk setiap baris tagihan yang statusnya belum lunas (`unpaid` atau `partial`), ditambahkan tombol **Kirim Notif** di kolom aksi (di sebelah tombol **Bayar**).
- Tombol ini dibungkus form inline method `POST` ke route `finance.reminders` dengan input hidden `bill_ids[]` berisi ID tagihan tersebut.
- Dengan cara ini, kita dapat menggunakan kembali logika `sendReminders` yang sudah ada di `FinanceController`, yang menerima array `bill_ids` dan menangani looping pengiriman ke wali murid secara otomatis.

---

## 📂 Berkas yang Dimodifikasi

### 1. View Tagihan
*   **Path**: [bills.blade.php](file:///d:/laragon/www/simt-backend/resources/views/admin/finance/bills.blade.php)
*   **Perubahan**:
    - Tambahkan checkbox `auto_notify` pada modal generate massal.
    - Tambahkan kolom/tombol aksi `Kirim Notif` di dalam tabel tagihan.

### 2. Controller Keuangan
*   **Path**: [FinanceController.php](file:///d:/laragon/www/simt-backend/Modules/Finance/app/Http/Controllers/FinanceController.php)
*   **Perubahan**:
    - Update `generateBills(Request $request)` untuk memproses `auto_notify` dan memicu pengiriman `SendWaNotification`.
    - Sinkronisasi perubahan yang sama ke `app/Http/Controllers/Web/FinanceController.php` demi konsistensi codebase.

---

## 🧪 Rencana Uji Coba (Verifikasi)
1. Buka halaman `/finance/bills`.
2. Lakukan ujicoba pengiriman manual pada salah satu tagihan yang belum lunas dengan menekan tombol **Kirim Notif**. Pastikan flash message sukses muncul dan status pengiriman WA terekam di tabel log notifikasi `/admin/notification` (WA Connect).
3. Generate tagihan massal baru dengan mencentang pilihan **Kirim Notifikasi Otomatis ke WhatsApp Orangtua**. Pastikan notifikasi terkirim ke seluruh wali siswa aktif.
