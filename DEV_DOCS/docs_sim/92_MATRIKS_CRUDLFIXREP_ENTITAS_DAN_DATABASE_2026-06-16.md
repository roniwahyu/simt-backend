# Matriks CRUDLFIXRep Entitas dan Database SIMT Backend

Dokumen ini memetakan setiap entitas bisnis utama dalam sistem **SIMT Backend** ke dalam matriks aksi **CRUDLFIXRep** (Create, Read, Update, Delete, List, Filter, Import, Export, Report) beserta tabel database terkait dan endpoint/metode pengontrol (controller) yang bertanggung jawab.

---

## 1. Definisi Aksi Matriks (CRUDLFIXRep)

*   **C (Create)**: Mekanisme penambahan data baru (Web Form / API Endpoint / Otomatisasi).
*   **R (Read)**: Menampilkan detail data spesifik (Web View / API JSON / PDF Stream) dan validasi pencegahan kerentanan hak akses (IDOR).
*   **U (Update)**: Memperbarui data (Web Edit Form / API Put / Sinkronisasi status otomatis).
*   **D (Delete)**: Menghapus data (Hard Delete / Soft Delete) beserta batasan validasi keamanan (safety constraints).
*   **L (List)**: Halaman atau endpoint untuk menyajikan daftar data (Web Datatable / API Pagination) dengan mitigasi N+1 query.
*   **F (Filter)**: Parameter input dinamis untuk menyaring daftar data (Scope Query, kueri tanggal standar SQLite/MySQL).
*   **I (Import)**: Kemampuan mengunggah data masal melalui template berkas (Excel/CSV) dengan pemrosesan transaksional.
*   **X (Export)**: Mengekspor data ke format eksternal (Excel `.xlsx` / PDF).
*   **Rep (Report)**: Komputasi analitis data (Dashboard / PDF Laporan Bulanan / Model pembobotan rumus nilai rapor).

---

## 2. Matriks CRUDLFIXRep Entitas Bisnis

Berikut adalah matriks relasi entitas, skema tabel, dan metode controller penanggung jawab:

### 2.1 Modul Siswa (Student Module)
*   **Tabel Terkait**: `students`, `guardian_student` (pivot), `class_student` (pivot)
*   **Hak Akses RBAC**: `admin_sekolah` (Penuh), `tu` (Penuh), `guru` (Read/List), `wali` (Read/List anak sendiri).

| Aksi | Representasi Teknis (Code & Database) | Deskripsi / Validasi Keamanan |
| :--- | :--- | :--- |
| **C** | `StudentController@store` (Web) | Validasi keunikan NIS per tenant, enkripsi nisn menjadi blind hash (`nisn_bindex`), auto-provisions user akun `User` (role: `wali`) dan menghubungkan pivot jika input menyertakan handphone wali murid. |
| **R** | `StudentApiController@show` (API) | Menyajikan detail profile siswa beserta classes & guardians. Memiliki *ownership check* agar wali murid tidak dapat mengintip siswa lain (IDOR Protection). |
| **U** | `StudentController@update` (Web) | Memperbarui biodata siswa, validasi keunikan NIS dengan pengecualian ID siswa bersangkutan (`ignore`). |
| **D** | `StudentController@destroy` (Web) | Hard delete data siswa. Menghapus relasi kelas dan wali terkait. |
| **L** | `StudentController@index` (Web) & `StudentApiController@list` (API) | Paginasi default 50 data. Menggunakan *eager loading* `.with('classes.schoolYear')` untuk menghindari N+1 query. |
| **F** | Parameter `search` (Nama, NIS, `nisn_bindex` blind hash) & `class_id` | Menyaring data siswa aktif berdasarkan kelas terdaftar. |
| **I** | `StudentImportService@validate` & `@commit` | **3-Step Import Wizard** via Excel. Memvalidasi data, menaruh data valid di Cache (30 menit) berbasis UUID Token, menampilkan pratinjau error baris Excel, dan mengeksekusi impor dalam `DB::transaction` tunggal. |
| **X** | - | - |
| **Rep**| - | Informasi rekap data siswa terintegrasi di dalam Rapor & Presensi. |

---

### 2.2 Modul Akademik & Nilai (Academic & Grading Module)
*   **Tabel Terkait**: `school_classes`, `subjects`, `grades`
*   **Hak Akses RBAC**: `admin_sekolah` (Penuh), `tu` (Rombel/Mapel), `guru` (Input Nilai kelas diampu), `wali` (Read Rapor anak sendiri).

| Aksi | Representasi Teknis (Code & Database) | Deskripsi / Validasi Keamanan |
| :--- | :--- | :--- |
| **C** | `GradeController@store` (Web) | Menyimpan nilai masal siswa per rombel & mapel. Menggunakan logika `updateOrCreate` untuk mendeteksi data duplikat (kombinasi unik: student, subject, exam type, tenant). |
| **R** | `GradeController@show` (Web) & `AkademikApiController@grades` (API) | Menampilkan detail nilai tunggal atau daftar nilai per mata pelajaran. Proteksi kepemilikan data bagi wali murid via pivot `guardianStudents`. |
| **U** | `GradeController@update` (Web) | Memperbarui skor numerik (0-100) dan deskripsi catatan kemajuan belajar. |
| **D** | - | Dihilangkan di tingkat antarmuka untuk mencegah hilangnya data nilai, guru cukup memperbarui skor ke angka 0 atau menimpa nilai. |
| **L** | `GradeController@index` (Web) & `AkademikApiController@grades` (API) | Menampilkan entri data nilai siswa terdaftar. |
| **F** | Parameter `student_id`, `subject_id`, `type` (UH/TUGAS/UTS/UAS), `class_id` | Penyaringan nilai berdasarkan sub-kategori evaluasi akademik. |
| **I** | - | - |
| **X** | `GradeController@rapor` (PDF Stream) | Mengekspor Rapor Digital dalam bentuk dokumen PDF portrait A4 formal menggunakan template **[rapor-pdf.blade.php](file:///d:/laragon/www/simt-backend/Modules/Akademik/resources/views/grades/rapor-pdf.blade.php)**. |
| **Rep**| `GradeController@rapor` (Web) & `AkademikApiController@rapor` (API) | Menghitung akumulasi nilai rapor secara dinamis menggunakan pembobotan persentase Pengetahuan dan Keterampilan, konversi huruf predikat (A, B, C, D), serta asimilasi summary kehadiran bulanan. |

---

### 2.3 Modul Presensi (Attendance Module)
*   **Tabel Terkait**: `attendances`
*   **Hak Akses RBAC**: `admin_sekolah` (Penuh), `tu` (Penuh), `guru` (Input kelas diampu), `wali` (Read).

| Aksi | Representasi Teknis (Code & Database) | Deskripsi / Validasi Keamanan |
| :--- | :--- | :--- |
| **C** | `AttendanceController@store` (Web AJAX) | Menyimpan data presensi grid harian siswa. Status non-Hadir (`A`, `I`, `S`, `T`) secara otomatis mengantrikan notifikasi push WhatsApp ke wali murid bersangkutan via `SendWaNotification` job. |
| **R** | `AttendanceController@classGrid` (Web) | Memuat layar grid presensi rombel untuk tanggal tertentu. |
| **U** | `AttendanceController@store` | Menyimpan perubahan (misal dari Tanpa Keterangan menjadi Izin) menggunakan kueri updateOrCreate terisolasi. |
| **D** | - | Opsi hapus dinonaktifkan demi audit kehadiran siswa. |
| **L** | `AttendanceController@index` (Web) | Menyajikan halaman utama presensi sekolah. Guru dibatasi hanya melihat kelas yang diampunya saja (`teacher_id = user_id`). |
| **F** | Parameter `class_id`, `date`, `month` | Penyaringan daftar presensi per tanggal harian atau rekap bulanan. |
| **I** | - | - |
| **X** | `AttendanceController@exportRecap` (Web) | Mengekspor lembar rekap presensi bulanan siswa ke Excel `.xlsx` menggunakan `AttendanceRecapExport`. |
| **Rep**| `AttendanceController@rekap` (Web) | Menampilkan rekapitulasi jumlah kehadiran, sakit, izin, dan alpa (kehadiran akumulatif) per siswa dalam rentang satu bulan. |

---

### 2.4 Modul Keuangan SPP (Finance Module)
*   **Tabel Terkait**: `bills`, `payments`
*   **Hak Akses RBAC**: `admin_sekolah` (Penuh), `bendahara` (Penuh), `wali` (Read/API tunggakan anak).

| Aksi | Representasi Teknis (Code & Database) | Deskripsi / Validasi Keamanan |
| :--- | :--- | :--- |
| **C** | `FinanceController@generateBills` (Bulk) & `storeSingleBill` (Single Web) | Membuat tagihan massal per tahun ajaran aktif dengan opsi auto-antrian WA reminder, atau membuat tagihan tunggal untuk siswa terpilih. |
| **R** | `FinanceController@printReceipt` (Web PDF) | Mengeluarkan tanda terima / kwitansi pembayaran resmi berformat PDF dinamis dengan nomor kwitansi otomatis. |
| **U** | `FinanceController@updateBill` (Web) | Memperbarui nominal tagihan atau komponen. Secara otomatis memicu penyelerasan status bayar (`updateStatus()`) di basis data. |
| **D** | `FinanceController@destroyBill` (Web) | Penghapusan data tagihan. Dibatasi ketat hanya untuk tagihan yang statusnya masih belum dibayar (`unpaid`). |
| **L** | `FinanceController@bills` (Web) & `FinanceApiController@index` (API) | Menampilkan daftar seluruh tagihan beredar. API menyertakan relasi payments dan summary kalkulasi tunggakan siswa. |
| **F** | Parameter `student_id`, `status` (paid/partial/unpaid), `period` (Y-m), `method` | Penyaringan tagihan berdasarkan status bayar dan periode kas. |
| **I** | - | - |
| **X** | `FinanceController@exportBills` (Web) | Mengekspor rekapitulasi data tagihan beredar ke Excel `.xlsx` menggunakan `BillsRecapExport`. |
| **Rep**| `FinanceController@dashboard` & `reports` (Web) | Menyajikan visualisasi dashboard analitik keuangan (total tagihan, total terbayar, sisa tunggakan, rasio lunas, kas pembayaran terbaru) dan rekapitulasi tabel pendapatan bulanan. |

---

### 2.5 Modul Notifikasi & WhatsApp Gateway (Notification Module)
*   **Tabel Terkait**: `wa_notifications`
*   **Hak Akses RBAC**: `admin_sekolah` (Read/List/Connect), `tu` (Read/List/Connect).

| Aksi | Representasi Teknis (Code & Database) | Deskripsi / Validasi Keamanan |
| :--- | :--- | :--- |
| **C** | `SendWaNotification` (Job) & `NotificationController@toolsSend` (Web) | Membuat log pesan baru tipe `outgoing` di database dan menembakkan API request ke Node.js Gateway. Pesan masuk (`incoming`) dibuat melalui webhook callback. |
| **R** | - | Pesan dipratinjau dalam balon chat mockup smartphone di halaman WA Tools. |
| **U** | `NotificationController@deliveryCallback` | Webhook callback dari gateway memperbarui status pesan di database (`sent`, `failed`, dll) disertai pencatatan `message_id` di kolom JSON `payload`. |
| **D** | - | Log pesan bersifat permanen (read-only) untuk keperluan audit log komunikasi. |
| **L** | `/admin/notification/table` (Partial AJAX) & `/admin/notification/incoming-feed` (Partial AJAX) | Memuat baris log pesan terbaru secara real-time menggunakan AJAX polling 5 detik. |
| **F** | Konteks `tenant_id` | Pesan disaring secara global berdasarkan tenant pengirim. |
| **I** | - | - |
| **X** | - | - |
| **Rep**| - | Tampilan feedback status online/offline gateway dan status pendaftaran sesi. |

---

### 2.6 Modul Keamanan Sistem (Audit Log Module)
*   **Tabel Terkait**: `audit_logs`
*   **Hak Akses RBAC**: `superadmin` (Global View), `admin_sekolah` (Tenant View).

| Aksi | Representasi Teknis (Code & Database) | Deskripsi / Validasi Keamanan |
| :--- | :--- | :--- |
| **C** | Trait `Auditable` | Otomatis mencatat aksi manipulasi database (create, update, delete) pada model terproteksi. Menyimpan data sebelum (`old_values`) dan sesudah (`new_values`) dalam bentuk JSON. |
| **R** | - | Ditampilkan di panel admin. |
| **U** | - | Audit trail bersifat *immutable* (tidak dapat diubah). |
| **D** | - | Audit trail tidak dapat dihapus untuk memenuhi standar kepatuhan (*compliance*). |
| **L** | `/super/audit-logs` (Web) | Daftar seluruh riwayat audit log sistem. |
| **F** | Parameter `tenant_id` | Superadmin dapat menyaring riwayat audit log berdasarkan sekolah/tenant terpilih. |
| **I** | - | - |
| **X** | - | - |
| **Rep**| - | Audit trail list yang menampilkan riwayat lengkap PII rollback dan grace period. |

---

## 3. Matriks Otorisasi Berdasarkan Peran (Role) dan Cakupan Tenant (Tenant Scope)

Di bawah ini adalah matriks hak akses CRUDLFIXRep yang dipetakan langsung ke masing-masing Peran Pengguna (**User Role**) beserta tingkat isolasi data di tingkat penyewa (**Tenant Scope**):

### 3.1 Legenda Hak Akses
*   **ALL**: Akses Penuh (Create, Read, Update, Delete, List, Filter, Import, Export, Report).
*   **R/L**: Hanya melihat detail data (Read) dan daftar data (List) beserta penyaringan (Filter).
*   **C/U/L**: Menginput data baru (Create), memperbarui data (Update), dan melihat daftar (List/Filter) tanpa wewenang menghapus (Delete).
*   **OWN**: Hak akses kontekstual terisolasi yang hanya mengizinkan pembacaan/penulisan data milik pengguna bersangkutan (kepemilikan siswa terdaftar bagi Wali Murid, atau rombel diampu bagi Guru).
*   **-**: Tidak memiliki hak akses (Blocked).

### 3.2 Tabel Matriks Peran & Tenant Scope

| Entitas & Model Bisnis | Superadmin | Admin Sekolah | Kepala Madrasah | Tata Usaha (TU) | Bendahara | Guru | Wali Murid | Cakupan Tenant & Tingkat Isolasi Data |
| :--- | :---: | :---: | :---: | :---: | :---: | :---: | :---: | :--- |
| **Siswa** (`Student`) | R/L | ALL | R/L | ALL | - | R/L | OWN | **Tenant-Wide**: Disaring otomatis `tenant_id` aktif. Wali murid terisolasi via kepemilikan siswa. |
| **Rombel & Kelas** (`SchoolClass`) | - | ALL | R/L | ALL | - | R/L | - | **Tenant-Wide**: Terisolasi penuh per sekolah. |
| **Mata Pelajaran** (`Subject`) | - | ALL | R/L | ALL | - | R/L | - | **Tenant-Wide**: Terisolasi penuh per sekolah. |
| **Nilai Siswa** (`Grade`) | - | ALL | R/L | - | - | ALL (OWN) | OWN | **Tenant-Wide**: Guru dibatasi ke kelas diampunya. Wali dibatasi ke rapor anak kandungnya. |
| **Presensi** (`Attendance`) | - | ALL | R/L | ALL | - | ALL (OWN) | R/L (OWN) | **Tenant-Wide**: Guru hanya dapat menandai kelasnya. Wali melihat kehadiran bulanan anak. |
| **Tagihan SPP** (`Bill`) | - | ALL | R/L | - | ALL | - | OWN | **Tenant-Wide**: Bendahara mengelola tagihan sekolah. Wali hanya melihat tagihan anaknya. |
| **Pembayaran** (`Payment`) | - | ALL | R/L | - | ALL | - | OWN | **Tenant-Wide**: Bendahara mencatat kas. Wali melihat riwayat kwitansi anaknya. |
| **WA Logs** (`WaNotification`) | - | ALL | R/L | ALL | - | - | - | **Tenant-Wide**: Log keluar/masuk disaring per sekolah. |
| **Audit Logs** (`AuditLog`) | ALL | R/L | - | - | - | - | - | **Lintas-Tenant**: Superadmin melihat global. Admin Sekolah terisolasi ke data log sekolahnya. |
| **Tenant** (`Tenant`) | ALL | - | - | - | - | - | - | **Lintas-Tenant**: Pengelolaan SaaS global oleh Superadmin. |

---
*Matriks CRUDLFIXRep dan Otorisasi Peran didefinisikan secara komprehensif berdasarkan skema database migrasi aktif dan logika Eloquent Controller per tanggal 16 Juni 2026.*
