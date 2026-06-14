# Rencana Implementasi: Mengamankan Rute & Parameter Presensi (Attendance)

Dokumen ini merinci rencana teknis untuk mengamankan halaman presensi dengan membatasi hak akses bagi pengguna ber-role `guru` ke kelas masing-masing, serta mengganti URL berbasis parameter query mentah dengan RESTful URL yang bersih.

## 🎯 Target Utama
1. **Keamanan Hak Akses (Authorization)**:
   - Pengguna dengan peran `guru` hanya boleh melihat, mengubah, dan melihat rekap untuk kelas yang ditugaskan kepada mereka (`teacher_id = auth()->id()`).
   - Peran administratif (`superadmin`, `admin_sekolah`, `kepala_madrasah`, `tu`) tetap memiliki akses penuh ke seluruh kelas.
   - Peran di luar di atas (seperti `wali` atau `bendahara`) harus diblokir total dari halaman presensi (`403 Forbidden`).
2. **URL & Endpoint yang Lebih Aman**:
   - Ganti URL `http://127.0.0.1:8000/attendance?class_id=1&date=2026-06-14` menjadi `/attendance/class/{class}/{date?}` yang menggunakan Route Model Binding dan divalidasi dengan benar.

---

## 📂 Daftar Perubahan File

### 1. 🛣️ [web.php](file:///d:/laragon/www/simt-backend/Modules/Attendance/routes/web.php)
- Memperbarui rute presensi agar menerima parameter kelas dan tanggal opsional dalam bentuk path:
  `Route::get('/attendance/class/{class}/{date?}', [AttendanceController::class, 'classGrid'])->name('attendance.grid');`

### 2. 🎮 [AttendanceController.php](file:///d:/laragon/www/simt-backend/Modules/Attendance/app/Http/Controllers/AttendanceController.php)
- **`index(Request $request)`**:
  - Filter daftar kelas berdasarkan hak akses pengguna. Jika role = `guru`, ambil hanya kelas yang diajar oleh user tersebut.
  - Jika parameter `class_id` dikirimkan, validasi kembali apakah kelas tersebut tergolong ke dalam daftar kelas yang boleh diakses user.
- **`classGrid(Request $request, SchoolClass $class, $date = null)`**:
  - Tangkap parameter kelas dari model binding, dan tanggal opsional.
  - Periksa hak akses untuk kelas tersebut (jika `guru` dan `teacher_id !== auth()->id()`, lempar `403 Forbidden`).
  - Merge parameter ke `$request` dan panggil `index($request)`.
- **`store(Request $request)`**:
  - Validasi bahwa pengguna memiliki izin `mark_attendance` / `edit_attendance`.
  - Jika role = `guru`, pastikan target `class_id` cocok dengan kelas yang diajar.
- **`rekap(Request $request)`** & **`exportRecap(Request $request)`**:
  - Validasi izin `view_attendance_rekap`.
  - Jika role = `guru`, pastikan target `class_id` cocok dengan kelas yang diajar.

### 3. 🎨 [index.blade.php](file:///d:/laragon/www/simt-backend/resources/views/admin/attendance/index.blade.php)
- Ubah form pilihan kelas/tanggal agar memicu perubahan URL halaman secara langsung ke `/attendance/class/{class}/{date}` melalui JavaScript, alih-alih melakukan submit `GET` dengan query string.
- Ubah pemanggilan endpoint simpan agar sesuai dengan rute.

---

## 🧪 Rencana Verifikasi

### Uji Coba Otomatis (Automated Tests)
- Menjalankan kembali seluruh test suite (`php artisan test`) untuk memastikan tidak ada fungsionalitas lama yang rusak.
- Menambahkan test case baru di `tests/Feature/AttendanceModuleTest.php`:
  1. `guru_can_access_own_class()`: Guru bisa melihat & mengedit kelas yang diajarnya.
  2. `guru_cannot_access_other_class()`: Guru mendapat `403 Forbidden` saat mencoba mengakses kelas guru lain via URL.
  3. `bendahara_and_wali_cannot_access_attendance()`: Peran yang tidak memiliki izin presensi diblokir total.

### Uji Coba Manual (Manual Verification)
- Login sebagai guru, periksa dropdown kelas presensi (hanya kelas miliknya yang harus muncul).
- Coba ketik manual URL kelas lain (misal: `/attendance/class/2`) dan pastikan diblokir dengan halaman error `403`.
