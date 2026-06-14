# SESSION HANDOVER — WhatsApp Gateway Connection & Reconnect Fix

**Tanggal:** 14 Juni 2026  
**Session ID:** 3c5c55de-8b8d-4e31-b1c5-79660489fdd5  
**Status:** ✅ WhatsApp Gateway Stabil & Teruji End-to-End  

---

## 1. Ringkasan Perbaikan yang Dilakukan

Pada sesi ini, kami mengatasi kendala pada WhatsApp Gateway (`simt-wa-gateway`) yang sebelumnya gagal terhubung ke server WhatsApp dengan log:
`Session 1: Connection closed. Reason: Error: Connection Failure. Reconnecting: true`

### 1.1 Solusi `405 Method Not Allowed` / `reason: "405", location: "frc"`
*   **Penyebab**: WhatsApp memperbarui sistem WebSocket-nya, sehingga Baileys yang menggunakan versi hardcoded lama ditolak oleh server WhatsApp.
*   **Solusi**: Kami mengimpor dan menggunakan `fetchLatestBaileysVersion` dari `@whiskeysockets/baileys` di [**`src/index.ts`**](file:///d:/laragon/www/simt-wa-gateway/src/index.ts) untuk mendeteksi versi WhatsApp Web terbaru secara dinamis pada saat inisialisasi socket.

### 1.2 Perbaikan Logika Rekoneksi (Reconnect Bug)
*   **Penyebab**: Sebelumnya, ketika koneksi gagal, sistem merubah status menjadi `'CONNECTING'` lalu memanggil kembali `startSession()`. Namun, di dalam `startSession()` terdapat pengecekan guard:
    ```typescript
    if (existing.status === 'CONNECTED' || existing.status === 'CONNECTING') {
      return existing; // Guard memblokir rekoneksi socket baru!
    }
    ```
    Hal ini membuat server macet pada status `'CONNECTING'` dan tidak pernah benar-benar membuat socket koneksi baru.
*   **Solusi**:
    *   Menambahkan parameter `force = true` pada signature `startSession(tenantId, force = false)`.
    *   Mengosongkan referensi socket lama (`sessionObj.socket = undefined`).
    *   Menggunakan `setTimeout` dengan jeda 5 detik sebelum memanggil kembali `startSession(tenantId, true)` guna mencegah terjadinya *tight retry loop* (spam ke server WhatsApp).

### 1.3 Penambahan `.gitignore`
*   Membuat berkas `.gitignore` di root folder `simt-wa-gateway` untuk mengecualikan:
    *   `node_modules/` dan build output (`dist/`).
    *   Kredensial sesi WhatsApp yang sensitif (`sessions/`, `auth_info/`, `*.store.json`).
    *   File log, file lingkungan (`.env`, `.env.*`), serta file sampah OS/Editor.

---

## 2. Hasil Verifikasi Pengujian (End-to-End Test)

Kami memicu pengujian dari Laravel Backend ke WA Gateway menggunakan `php83` dengan skenario sebagai berikut:

1.  **Status Koneksi**: Sesi WA untuk Tenant `5` (`mts-alhikmah`) aktif terhubung (`CONNECTED`) ke nomor `6285804628337`.
2.  **Job Dispatch**: Memicu pengiriman notifikasi presensi menggunakan Job `SendWaNotification` untuk tenant `5` ke nomor tujuan `6285804628337`.
3.  **Delivery Callback**:
    *   WhatsApp Gateway sukses mengirim pesan.
    *   WhatsApp Gateway memicu webhook callback ke endpoint Laravel `POST /api/v1/wa/delivery-callback` dengan secret token yang valid.
    *   Laravel berhasil menerima callback, memetakan status `delivered` menjadi `sent`, lalu memperbarui tabel `wa_notifications` dengan mengisi kolom `sent_at` secara instan.

**Data Verifikasi Database (`wa_notifications`):**
```text
ID: 8 | Tenant: 5 | Phone: 6285804628337 | Status: sent | Sent At: 2026-06-14 14:38:05 | Error: (none)
```

---

## 3. Instruksi untuk Agen di Sesi Berikutnya

1.  **Server WhatsApp Gateway**: Pastikan Node.js gateway dijalankan menggunakan `npm run dev` pada port `8081` di direktori `simt-wa-gateway`.
2.  **Laravel Backend**: Jalankan server Laravel pada port `8000` menggunakan perintah:
    ```bash
    php83 artisan serve --port=8000
    ```
3.  **Verifikasi Lanjutan**: Semua pengujian unit pada `NotificationModuleTest` telah lulus 100% (4 passed).
