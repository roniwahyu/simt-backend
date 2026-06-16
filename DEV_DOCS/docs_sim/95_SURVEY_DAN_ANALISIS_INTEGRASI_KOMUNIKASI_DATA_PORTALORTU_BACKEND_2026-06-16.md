# Analisis Mendalam: Kesiapan Komunikasi Data Portalortu (Next.js) & Backend (Laravel)

Dokumen ini menyajikan hasil survey, pemetaan skema, dan analisis kesiapan teknis integrasi komunikasi data antara **simt-portalortu** (Next.js) dan **simt-backend** (Laravel) dari kedua belah sisi (Frontend BFF & Backend REST API).

---

## 1. Apakah Portalortu Sudah Ready untuk Real-Time Data (Bukan Mockup)?

**Status: Belum Ready (Masih Menggunakan SQLite + Prisma Lokal)**

Berdasarkan analisis pada kode sumber [simt-portalortu](file:///d:/laragon/www/simt-portalortu/), status kesiapannya adalah sebagai berikut:
1. **Otorisasi dan API Internal**: Next.js menggunakan *App Router* dengan API internal di direktori `src/app/api/`. Antarmuka frontend (misal [page.tsx](file:///d:/laragon/www/simt-portalortu/src/app/page.tsx)) memanggil API internal Next.js sendiri (seperti `/api/dashboard` dan `/api/student-auth`).
2. **Ketergantungan database**: API internal tersebut melakukan query langsung ke database SQLite lokal (`d:/laragon/www/simt-portalortu/db/custom.db`) menggunakan **Prisma ORM** sesuai yang didefinisikan dalam [schema.prisma](file:///d:/laragon/www/simt-portalortu/prisma/schema.prisma).
3. **Kesimpulan**: Portalortu saat ini masih berjalan secara terisolasi menggunakan data tiruan (*seeded mockup data*). Untuk beralih ke data real-time, rute API internal Next.js tersebut harus di-refactor agar tidak melakukan query ke Prisma lokal, melainkan bertindak sebagai *BFF (Backend-for-Frontend) Proxy* yang menembak REST API Laravel Backend via HTTP.

---

## 2. Apakah Data Tersebut Sudah Tersedia di Laravel Backend?

**Status: Tersedia Sebagian (Ada Kesenjangan Skema Entitas Penting)**

Berikut adalah matriks ketersediaan tabel/model data di Laravel Backend dibandingkan dengan kebutuhan di Next.js Portalortu:

| Entitas Data di Portalortu | Tabel Terkait di Next.js | Ketersediaan di Laravel Backend | Keterangan / Solusi |
| :--- | :--- | :---: | :--- |
| **Siswa (Student)** | `students` | **Tersedia** | Perlu penambahan field `photo` dan informasi login portal (`student_password`). |
| **Kehadiran (Attendance)** | `attendances` | **Tersedia** | Ada perbedaan format status presensi (lihat bagian 3). |
| **Tagihan & SPP (Payment)** | `payments` | **Tersedia** | Di Laravel terbagi menjadi tabel `bills` (piutang) dan `payments` (transaksi). |
| **Pengumuman (Announcement)** | `announcements` | **Tersedia** | Siap digunakan. |
| **Jadwal Pelajaran (Schedule)** | `schedules` | ❌ **Belum Ada** | Harus dibuat tabel, model, dan seeder di backend. |
| **Pelanggaran (Violation)** | `student_violations` | ❌ **Belum Ada** | Harus dibuat tabel, model, dan seeder di backend. |
| **Prestasi (Achievement)** | `student_achievements` | ❌ **Belum Ada** | Harus dibuat tabel, model, dan seeder di backend. |
| **Hafalan Quran (Tahfiz)** | `tahfiz_records` | ❌ **Belum Ada** | Harus dibuat tabel, model, dan seeder di backend. |
| **Rincian Nilai (Grade Detail)** | `grade_details` | ❌ **Belum Ada** | Harus dibuat tabel, model, dan seeder di backend. |

---

## 3. Pemetaan & Analisis Kesenjangan Teknis (Gap Analysis)

Setelah melakukan survey mendalam pada kedua repositori, ditemukan tiga kesenjangan struktural utama yang menghalangi koneksi database langsung:

### Kesenjangan 1: Tipe Data ID (Primary Key Mismatch)
*   **Next.js Portalortu (Prisma)**: Menggunakan CUID / UUID string (`id String @id @default(cuid())`).
*   **Laravel Backend (MySQL)**: Menggunakan Auto-Incrementing Big Integer (`id bigint unsigned`).
*   **Dampak**: Next.js tidak bisa dihubungkan ke MySQL Laravel secara langsung via Prisma karena Prisma akan gagal memvalidasi relasi integer dengan skema CUID string yang ada pada frontend.

### Kesenjangan 2: Relasi Siswa ke Rombel/Kelas
*   **Next.js Portalortu**: Relasi 1-to-many sederhana. Entitas `Student` memiliki field `classroomId` yang merujuk langsung ke tabel `classrooms`.
*   **Laravel Backend**: Relasi many-to-many dinamis. Siswa terhubung ke kelas melalui tabel pivot `class_student` yang melacak sejarah kelas siswa berdasarkan `school_year_id` (Tahun Ajaran aktif).
*   **Dampak**: Query langsung dari Prisma Next.js akan gagal karena tabel `students` di Laravel tidak memiliki kolom `classroom_id`.

### Kesenjangan 3: Standardisasi Status (Enum Mappings)
*   **Status Presensi**:
    *   Next.js: `'HADIR'`, `'SAKIT'`, `'IZIN'`, `'ALPHA'` (String panjang).
    *   Laravel: `'H'`, `'A'`, `'I'`, `'S'`, `'T'` (1 karakter).
*   **Status Keuangan**:
    *   Next.js: `'BELUM_BAYAR'`, `'MENUNGGU'`, `'LUNAS'`, `'SEBAGIAN'` (String panjang).
    *   Laravel: `'unpaid'`, `'partial'`, `'paid'` (Status tagihan pada tabel `bills`).

---

## 4. Rekomendasi Aliran Komunikasi Data (Data Flow Architecture)

Karena adanya ketidakcocokan skema database yang sangat mendasar (BigInt vs CUID, Rombel Pivot vs Direct ID), **Skenario A (Menghubungkan Prisma Next.js langsung ke MySQL Laravel) sangat tidak direkomendasikan** karena akan merusak arsitektur database Laravel.

**Skenario B (Komunikasi REST API dengan BFF Mapping) adalah Solusi Terbaik**:

```
 ┌────────────────────────┐           ┌────────────────────────┐           ┌────────────────────────┐
 │ Portalortu Frontend    │           │ Next.js BFF (API Route)│           │ Laravel Backend        │
 ├────────────────────────┤           ├────────────────────────┤           ├────────────────────────┤
 │ - Render UI Komponen   │           │ - Terima Request       │           │ - Terima Auth Token    │
 │ - Render State Lokal   │ ────────> │ - Ambil Token Sanctum  │ ────────> │ - Jalankan Tenant Scope│
 │ - Tangani Input User   │ <──────── │ - Fetch REST API       │ <──────── │ - Kembalikan DB Data   │
 │                        │           │ - Petakan JSON / Enum  │           │   (BigInt, Snake_case) │
 └────────────────────────┘           └────────────────────────┘           └────────────────────────┘
```

### Penjelasan Aliran:
1. Halaman web Next.js tetap menembak `/api/dashboard?studentId=...` lokal.
2. Next.js Route Handler (`route.ts`) menangkap request tersebut, membaca Sanctum Token dari session/header, lalu melakukan HTTP fetch ke Laravel Backend:
   `GET http://localhost:8000/api/v1/portal/students/{student}/dashboard` dengan menyertakan header `Authorization: Bearer <token>` dan `X-Tenant-Domain: <tenant-slug>`.
3. Laravel memproses request, memfilter data secara ketat menggunakan global scope `BelongsToTenant` (mencegah kebocoran data antar tenant), memvalidasi kepemilikan wali murid (mencegah IDOR), dan mengembalikan data dalam format JSON.
4. Next.js Route Handler melakukan **data transformation** (misal: memetakan `'H'` menjadi `'HADIR'`, memetakan BigInt ID menjadi CUID mock, atau menyederhanakan data pivot kelas) sebelum mengirimkannya kembali ke frontend Next.js.

---

## 5. Langkah-Langkah Tindakan (Action Plan)

1.  **Backend (Laravel)**:
    *   Eksekusi migrasi tabel baru (`schedules`, `student_violations`, `student_achievements`, `tahfiz_records`, `grade_details`) dan penambahan kolom pada `students`.
    *   Implementasi `PortalOrtuApiController` di Laravel dengan endpoint `/api/v1/portal/...` yang mengembalikan data terkomputasi (seperti nilai akhir rata-rata dan status presensi terpetakan).
    *   Menulis unit test `PortalOrtuApiTest` untuk memastikan isolasi tenant.
2.  **Frontend (Next.js)**:
    *   Modifikasi API Route internal Next.js (seperti `src/app/api/dashboard/route.ts`) untuk melakukan fetch HTTP ke Laravel REST API alih-alih memanggil `db.student` lokal.
