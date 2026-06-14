# DEV REPORT: Mengamankan Rute & Parameter Presensi (Attendance)

**Tanggal:** 14 Juni 2026  
**Status:** ✅ **SELESAI** — 37 passed tests  

Dokumen ini merangkum perubahan yang dilakukan untuk mengamankan halaman presensi (attendance) agar guru hanya dapat mengakses kelasnya sendiri serta mengubah URL agar bersih dan aman dari manipulasi parameter query.

---

## 🛠️ Ringkasan Perubahan

### 1. Rute & URL Baru
- **File:** `Modules/Attendance/routes/web.php`
- **Sebelumnya:** `/attendance/class/{class}` (dan form melakukan submit `GET` ke `/attendance?class_id=X&date=YYYY-MM-DD`)
- **Sesudahnya:** `/attendance/class/{class}/{date?}`
- **Tambahan Keamanan:** Menggunakan Route Model Binding untuk kelas dan validasi pola regex tanggal (`[0-9]{4}-[0-9]{2}-[0-9]{2}`) guna mencegah kolisi dengan rute lain.

### 2. Otoritas di Level Controller
- **File:** `Modules/Attendance/app/Http/Controllers/AttendanceController.php`
- **Pengecekan Izin Spatie:** Memastikan pengguna memiliki izin yang relevan (`view_attendance`, `mark_attendance`, `edit_attendance`, `view_attendance_rekap`).
- **Isolasi Kelas per Guru:**
  - Jika pengguna ber-role `guru`, sistem menyaring daftar kelas agar hanya memuat kelas yang diajar oleh guru tersebut (`teacher_id = auth()->id()`).
  - Apabila guru mencoba mengakses kelas milik guru lain (baik lewat dropdown, query string, maupun rute langsung), sistem akan langsung memotong dengan respon `403 Forbidden`.
  - Guru diizinkan melihat rekap bulanan & ekspor rekap khusus untuk kelas yang diajarnya saja (meskipun tidak memiliki izin rekap global `view_attendance_rekap`).
  - Peran administratif (`superadmin`, `admin_sekolah`, `kepala_madrasah`, `tu`) tetap dapat mengakses seluruh kelas seperti biasa.

### 3. Pembersihan URL di Sisi Tampilan (View)
- **File:** `resources/views/admin/attendance/index.blade.php`
- Menambahkan fungsi JavaScript `navigateToGrid()` yang secara otomatis mengarahkan browser ke rute URL bersih `/attendance/class/{class}/{date}` saat pengguna mengubah pilihan kelas atau tanggal di dropdown, alih-alih men-submit query parameter mentah.

### 4. Penambahan Test Unit/Feature
- **File:** `tests/Feature/AttendanceModuleTest.php`
- Menambahkan 3 test baru untuk memverifikasi kebijakan keamanan di atas:
  1. `guru_can_access_own_class_attendance_and_recap()` — Sukses mengakses kelas yang diajar.
  2. `guru_cannot_access_other_teachers_class_attendance_or_recap()` — Diblokir `403` jika mencoba mengakses kelas guru lain.
  3. `unauthorized_roles_blocked_from_attendance_module()` — Memastikan peran lain tanpa izin (seperti `bendahara`) diblokir total.

---

## 🧪 Hasil Pengujian
Pengujian otomatis dijalankan pada database uji khusus `simt_backend_testing` dan seluruhnya berhasil (**37 passed**):

```
   PASS  Tests\Feature\AttendanceModuleTest
  ✓ guru can save attendance grid and marked by is recorded
  ✓ attendance is unique per student per date
  ✓ monthly recap page is accessible
  ✓ attendance module disabled returns 403
  ✓ attendance is isolated per tenant
  ✓ monthly recap export is accessible
  ✓ guru can access own class attendance and recap
  ✓ guru cannot access other teachers class attendance or recap
  ✓ unauthorized roles blocked from attendance module

  Tests:    37 passed (90 assertions)
  Duration: 17.40s
```
Semua rute presensi kini 100% aman dan berjalan dengan baik.
