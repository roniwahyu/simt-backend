# SESSION CONTEXT & HANDOVER (Sesi 8 Akhir: Premium Login, WA Tools & Live Webhook Tracing)
## SIMT MTs — Dokumen Handover Lengkap untuk Melanjutkan Sesi Berikutnya

**Tanggal Sesi:** 14 Juni 2026  
**Agent:** Antigravity (Sesi 8: Premium Login, WA Tools & Live Webhook Tracing)  
**Status Akhir:** ✅ Login UI Premium & Autofill Selesai · ✅ Real-time incoming WA captured · ✅ WA Tools & WYSIWYG Markdown Selesai · ✅ DB Seeding Lulus Hijau  
**Tujuan Dokumen:** Mencarat seluruh berkas yang dibuat, diubah, dan diuji selama Sesi 8 agar sesi berikutnya memiliki konteks 100% lengkap.

---

## 1. PENCAPAIAN SESI INI (SESI 8)

### A. Antarmuka Login Premium & Akses Cepat Demo (Autofill)
- **Modifikasi**: Desain ulang total halaman [login.blade.php](file:///d:/laragon/www/simt-backend/resources/views/auth/login.blade.php) menjadi bertema gelap (*dark-mode*) premium dengan pendaran latar belakang *glow mesh* dinamis.
- **Fitur Baru**: Menambahkan panel **Akses Cepat Demo Akun** berikon SVG untuk mengisi formulir otomatis saat diklik (ripples, scale, dan flash effects pada tombol masuk).
- **Akun Demo Terkait**:
  1. `vendor@simt.id` — *Superadmin Lintas Tenant*
  2. `ahmad@mts-alhikmah.sch.id` — *Admin Sekolah (Tenant 1 - Al-Hikmah - Modul Lengkap)*
  3. `ahmad@mts-annur.sch.id` — *Guru (Tenant 2 - An-Nur - Tanpa Modul Keuangan)*
  4. No. HP `628520000001` — *Wali Murid (Wali Muhammad Rizki di Al-Hikmah)*

### B. Tracing Detail Pesan Masuk & Keluar (WhatsApp Logs)
- **Gateway Node.js**: Memodifikasi [index.ts](file:///d:/laragon/www/simt-wa-gateway/src/index.ts) pada gateway untuk mendengarkan event `messages.upsert` (Baileys) dan meneruskan `senderName` (pushName), `messageId` (Baileys JID ID), nomor pengirim, dan teks pesan ke Laravel webhook.
- **Laravel Webhook**: Memodifikasi `deliveryCallback` pada [NotificationController.php](file:///d:/laragon/www/simt-backend/Modules/Notification/app/Http/Controllers/NotificationController.php) untuk memproses event `message_received` dan menyimpannya sebagai record `WaNotification` bertipe `incoming` dengan status `sent`.
- **Log Pelacakan ID**: Mengintegrasikan penyimpanan `message_id` di dalam kolom `payload` JSON untuk seluruh pesan masuk dan keluar (diambil dari respons gateway saat job [SendWaNotification.php](file:///d:/laragon/www/simt-backend/app/Jobs/SendWaNotification.php) sukses dieksekusi).

### C. Live Polling Halaman WA Connect
- **Tabel Antrean Connect**: Memodifikasi [connect.blade.php](file:///d:/laragon/www/simt-backend/Modules/Notification/resources/views/connect.blade.php) agar menampilkan arah (**Masuk/Keluar**), nama kontak pengirim, isi pesan langsung (*message body preview*), dan ID pelacak WhatsApp.
- **AJAX Polling**: Halaman melakukan refresh tabel secara dinamis setiap 5 detik ke endpoint `/admin/notification/table` (HTML partial) tanpa perlu memuat ulang seluruh halaman.

### D. Halaman Baru: WA Tools & WYSIWYG Markdown Editor
- **Tampilan Utama**: Membuat halaman [tools.blade.php](file:///d:/laragon/www/simt-backend/Modules/Notification/resources/views/tools.blade.php) yang membagi ruang kerja menjadi form pengiriman (kiri) dan inbox beserta live preview WhatsApp (kanan).
- **EasyMDE Editor**: Menggunakan Markdown WYSIWYG editor (EasyMDE) yang secara otomatis memetakan teks Markdown (`**tebal**`, `_miring_`, `~~coret~~`) ke format pemformatan WhatsApp asli (`*tebal*`, `_miring_`, `~coret~`).
- **Pratinjau Balon Chat**: Menampilkan mockup layar smartphone dengan balon chat hijau WhatsApp yang merender pesan secara instan sesuai hasil pengetikan di editor.
- **Kotak Masuk & Balas Cepat**: Menampilkan feed pesan masuk secara real-time (AJAX 5s). Mengeklik tombol **Balas** pada pesan masuk otomatis menyalin nomor tujuan, mencocokkan tenant, mengutip teks pesan asli (*quote block*), dan memfokuskan kursor ke editor Markdown.

---

## 2. PETA BERKAS BARU & DIMODIFIKASI

```
simt-backend/
├── app/Jobs/SendWaNotification.php            # [MODIFIED] Simpan message_id di payload, fix backoff array type
├── resources/views/auth/login.blade.php       # [MODIFIED] Premium dark theme login + interactive autofill
├── resources/views/layouts/app.blade.php      # [MODIFIED] Tambah link navigasi "WA Tools" di sidebar
├── Modules/Notification/
│   ├── app/Http/Controllers/NotificationController.php # [MODIFIED] Logika tools, toolsSend, incomingFeed, & detail tracing
│   ├── resources/views/
│   │   ├── connect.blade.php                  # [MODIFIED] Tampilkan Arah/Pesan, AJAX polling 5s
│   │   ├── tools.blade.php                    # [NEW] WA Tools & Markdown WYSIWYG editor
│   │   └── partials/
│   │       ├── table-rows.blade.php           # [NEW] Partial untuk real-time update tabel Connect
│   │       └── incoming-feed.blade.php        # [NEW] Partial untuk real-time update feed WA Tools
│   └── routes/web.php                         # [MODIFIED] Registrasi rute /table, /tools, /tools/send, /incoming-feed
│
simt-wa-gateway/
└── src/index.ts                               # [MODIFIED] Tangkap messages.upsert, webhook kirim pushName & messageId
```

---

## 3. HASIL VERIFIKASI PENGUJIAN

1.  **Unit Tests**: Seluruh 28 unit testing (63 assertions) berjalan sukses lulus hijau.
2.  **Blade View Compiles**: Halaman `tools.blade.php` dan partial views sukses diuji render melalui Tinker (`view('notification::tools')->render()`) tanpa *syntax error*.
3.  **End-to-End Callback**: Pengiriman mock webhook dengan parameter `senderName` dan `messageId` sukses diterima (`200 OK`) dan terekam di MySQL:
    ```sql
    select id, tenant_id, to_phone, type, payload, status from wa_notifications order by id desc limit 1;
    -- +----+-----------+---------------+----------+------------------------------------------------------------------------------------------------------------------------------------------+--------+
    -- | id | tenant_id | to_phone      | type     | payload                                                                                                                                  | status |
    -- +----+-----------+---------------+----------+------------------------------------------------------------------------------------------------------------------------------------------+--------+
    -- |  5 |         5 | 6281331711385 | incoming | {"message":"Halo Admin, terima kasih atas infonya. Saya sangat menyukai dashboard baru ini!","sender_name":"Ahmad Haisyam","message_id":"BAILEYS-MOCK-99281A"} | sent   |
    -- +----+-----------+---------------+----------+------------------------------------------------------------------------------------------------------------------------------------------+--------+
    ```

---

## 4. INSTRUKSI MULAI CEPAT UNTUK SESI BERIKUTNYA

Apabila sesi sandbox di-restart atau dipulihkan di lingkungan baru, jalankan perintah pemulihan berikut:
```bash
# 1. Pastikan database terisi data demo
php83 artisan db:seed

# 2. Bersihkan seluruh cache Laravel
php83 artisan view:clear && php83 artisan config:clear && php83 artisan route:clear

# 3. Jalankan server Laravel (port 8000)
php83 artisan serve --port=8000

# 4. Pastikan WA Gateway di port 8081 aktif & terhubung
# cd ../simt-wa-gateway && npm run dev
```

*Dokumen handover ditulis dengan seksama pada 14 Juni 2026. Seluruh repositori berada dalam kondisi bersih, terkompilasi sukses, dan siap dilanjutkan untuk Sprint 5 berikutnya.*
