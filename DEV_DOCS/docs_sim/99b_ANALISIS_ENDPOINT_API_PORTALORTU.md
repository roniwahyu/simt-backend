# Analisis Endpoint API Portal Orang Tua - SIMT Backend

**Tanggal:** 16 Juni 2026  
**Repo Backend:** `simt-backend` (Laravel 11, PHP 8.3)  
**Repo Frontend:** `simt-portalortu` (Next.js 16.1.1 / React 19)  
**Tujuan:** Verifikasi kesiapan API untuk aplikasi Portal Orang Tua

---

## 📋 Ringkasan Eksekutif

**Status: SUDAH SIAP ✅**

Backend Laravel telah menyediakan endpoint lengkap per-siswa untuk mendukung aplikasi Portal Orang Tua dengan fitur:
- ✅ Autentikasi (wali murid & siswa)
- ✅ Dashboard lengkap (presensi, nilai, SPP)
- ✅ Detail nilai per mata pelajaran
- ✅ Data tambahan (jadwal, pelanggaran, prestasi, tahfiz)

**Catatan:** Repo `simt-portalortu` berada di workspace terpisah dan memerlukan konfigurasi base URL untuk mengakses API backend.

---

## 1. Daftar Endpoint API

### 1.1 Autentikasi

| Method | Endpoint | Deskripsi | Status |
|--------|----------|-----------|--------|
| POST | `/api/v1/auth/parent-login` | Login wali murid dengan email/password | ✅ Aktif |
| POST | `/api/v1/auth/student-login` | Login siswa dengan NIS/password | ✅ Aktif |
| GET | `/api/v1/me` | Info user/siswa yang sedang login | ✅ Aktif |
| GET | `/api/v1/me/children` | Daftar anak (untuk wali murid) | ✅ Aktif |
| POST | `/api/v1/logout` | Logout dan revoke token | ✅ Aktif |

### 1.2 Dashboard & Data Siswa

| Method | Endpoint | Deskripsi | Status |
|--------|----------|-----------|--------|
| GET | `/api/v1/portal/students/{student}/dashboard` | Dashboard lengkap untuk wali murid | ✅ Aktif |
| GET | `/api/v1/portal/students/{student}/student-dashboard` | Dashboard extended untuk siswa | ✅ Aktif |
| GET | `/api/v1/portal/students/{student}/subjects/{subject}/grade-details` | Detail nilai per mata pelajaran | ✅ Aktif |

### 1.3 Endpoint Granular (OPSIONAL)

| Method | Endpoint | Deskripsi | Status |
|--------|----------|-----------|--------|
| GET | `/api/v1/students/{student}/attendance` | Presensi bulanan per siswa | ⚠️ Controller ada, belum terdaftar di route |
| GET | `/api/v1/students/{student}/bills` | Tagihan SPP per siswa | ⚠️ Controller ada, belum terdaftar di route |

---

## 2. Detail Endpoint dengan Contoh Response

### 2.1 Login Wali Murid

**Endpoint:** `POST /api/v1/auth/parent-login`

**Headers:**
```
Content-Type: application/json
X-Tenant-Domain: {tenant-slug}  // Contoh: mts-alhikmah
```

**Request Body:**
```json
{
  "email": "wali_0001@simt.local",
  "password": "password"
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "user": {
      "id": 5,
      "name": "Ahmad Fauzi (Wali)",
      "email": "wali_0001@simt.local"
    },
    "students": [
      {
        "id": 1,
        "name": "Muhammad Rizki",
        "nis": "0001",
        "classroom": "7A",
        "level": 7,
        "tenant": {
          "name": "MTs Al-Hikmah",
          "slug": "mts-alhikmah"
        }
      }
    ],
    "token": "1|abcdef123456..."
  }
}
```

**Response Error (401):**
```json
{
  "success": false,
  "message": "Kredensial tidak valid.",
  "code": "INVALID_CREDENTIALS"
}
```

---

### 2.2 Login Siswa

**Endpoint:** `POST /api/v1/auth/student-login`

**Headers:**
```
Content-Type: application/json
X-Tenant-Domain: {tenant-slug}
```

**Request Body:**
```json
{
  "nis": "0001",
  "password": "siswa123"
}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Login berhasil",
  "data": {
    "student": {
      "id": 1,
      "name": "Muhammad Rizki",
      "nis": "0001",
      "nisn": "0071234010",
      "gender": "L",
      "classroom": "7A",
      "level": 7,
      "tenant": {
        "name": "MTs Al-Hikmah",
        "slug": "mts-alhikmah"
      },
      "birthPlace": "Malang",
      "birthDate": "2012-05-15",
      "address": "Jl. Contoh No. 123",
      "photo": null
    },
    "token": "2|xyz789..."
  }
}
```

**Response Error (403 - Akun belum diaktivasi):**
```json
{
  "success": false,
  "message": "Akun siswa belum diaktifkan. Hubungi TU.",
  "code": "STUDENT_NOT_ACTIVATED"
}
```

---

### 2.3 Dashboard Wali Murid

**Endpoint:** `GET /api/v1/portal/students/{student}/dashboard`

**Headers:**
```
Authorization: Bearer {token}
X-Tenant-Domain: {tenant-slug}
```

**Query Parameters:**
- `gradeType` (optional): `PENGETAHUAN` | `KETERAMPILAN` | `UTS` | `UAS` | `SIKAP` (default: `PENGETAHUAN`)

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Berhasil memuat dashboard",
  "data": {
    "student": {
      "id": 1,
      "name": "Muhammad Rizki",
      "nis": "0001",
      "nisn": "0071234010",
      "gender": "L",
      "birthPlace": "Malang",
      "birthDate": "2012-05-15",
      "address": "Jl. Contoh No. 123",
      "photo": null,
      "fatherName": "Ahmad Fauzi",
      "fatherPhone": "081234567890",
      "motherName": "Siti Aminah",
      "motherPhone": "081234567891",
      "parentEmail": "wali_0001@simt.local",
      "classroom": {
        "id": 1,
        "name": "7A",
        "level": 7,
        "capacity": 36,
        "academicYear": {
          "id": 1,
          "name": "2025/2026",
          "semester": 2,
          "isActive": true
        },
        "waliKelas": {
          "name": "Ustadz Mahmud",
          "phone": "081234567892"
        }
      },
      "tenant": {
        "name": "MTs Al-Hikmah",
        "slug": "mts-alhikmah",
        "logo": null
      }
    },
    "attendanceSummary": {
      "hadir": 18,
      "sakit": 2,
      "izin": 1,
      "alpha": 0,
      "total": 21,
      "recent": [
        {
          "id": 1,
          "date": "2026-06-16",
          "status": "HADIR",
          "timeIn": "07:15:00",
          "timeOut": null,
          "notes": null
        }
      ],
      "daily": [...],
      "periodLabel": "Bulan Juni 2026",
      "hasData": true
    },
    "grades": {
      "list": [
        {
          "id": 1,
          "subjectId": 1,
          "subject": {
            "id": 1,
            "name": "Al-Qur'an",
            "code": "ALQ",
            "category": "UMUM"
          },
          "type": "PENGETAHUAN",
          "score": 85.5,
          "kkm": 75,
          "notes": "Tuntas",
          "teacher": {
            "name": "Ustadz Mahmud"
          }
        }
      ],
      "average": 82.3,
      "count": 12,
      "activeType": "PENGETAHUAN",
      "availableTypes": [
        {"type": "PENGETAHUAN", "count": 12},
        {"type": "KETERAMPILAN", "count": 12},
        {"type": "UTS", "count": 12},
        {"type": "UAS", "count": 12},
        {"type": "SIKAP", "count": 12}
      ],
      "hasData": true,
      "belowKKMCount": 2,
      "pengetahuanAverage": 82.3,
      "isAllTuntas": false
    },
    "payments": {
      "all": [
        {
          "id": 1,
          "type": "SPP",
          "amount": 200000,
          "month": 6,
          "year": 2026,
          "status": "LUNAS",
          "paidAmount": 200000,
          "paymentDate": "2026-06-10",
          "paymentMethod": "cash",
          "notes": null,
          "dueDate": "2026-06-15"
        }
      ],
      "unpaid": [],
      "totalUnpaid": 0,
      "totalPaid": 200000,
      "hasData": true
    },
    "announcements": [
      {
        "id": 1,
        "title": "Libur Akhir Semester",
        "content": "Libur akhir semester genap...",
        "category": "UMUM",
        "isPinned": true,
        "publishedAt": "2026-06-01T08:00:00Z",
        "expiresAt": null,
        "createdBy": {"name": "Admin Sekolah"}
      }
    ]
  }
}
```

---

### 2.4 Dashboard Siswa (Extended)

**Endpoint:** `GET /api/v1/portal/students/{student}/student-dashboard`

**Headers:** Sama seperti dashboard wali

**Response:** Sama seperti dashboard wali PLUS data tambahan:

```json
{
  "success": true,
  "message": "Berhasil memuat dashboard siswa",
  "data": {
    // ... semua data dari dashboard wali ...
    
    "schedules": [
      {
        "id": 1,
        "dayOfWeek": 1,
        "startPeriod": 1,
        "endPeriod": 2,
        "subject": {
          "id": 1,
          "name": "Al-Qur'an",
          "code": "ALQ"
        },
        "teacher": {
          "id": 3,
          "name": "Ustadz Mahmud",
          "phone": "081234567892"
        },
        "classroom": {
          "name": "7A"
        }
      }
    ],
    
    "violations": {
      "list": [
        {
          "id": 1,
          "date": "2026-05-20T00:00:00Z",
          "category": "KETERLAMBATAN",
          "description": "Terlambat 3x berturut-turut",
          "points": 5,
          "action": "Peringatan lisan",
          "handledBy": {"name": "Ustadz Mahmud"}
        }
      ],
      "totalPoints": 5,
      "count": 1
    },
    
    "achievements": {
      "list": [
        {
          "id": 1,
          "date": "2026-04-15T00:00:00Z",
          "title": "Juara 1 MTQ Tingkat Kabupaten",
          "category": "KEAGAMAAN",
          "level": "KABUPATEN",
          "ranking": "1",
          "organizer": "Kemenag Malang",
          "certificateUrl": "/storage/certificates/mtq_2026.pdf",
          "notes": "Juara 1 cabang Tilawah"
        }
      ],
      "count": 1
    },
    
    "tahfiz": {
      "totalRecords": 15,
      "ziyadahCount": 8,
      "murajaahCount": 7,
      "averageScore": 8.5,
      "surahMemorized": 5,
      "latestRecords": [
        {
          "id": 1,
          "date": "2026-06-15",
          "type": "ZIYADAH",
          "surah": "Al-Baqarah",
          "ayahStart": 1,
          "ayahEnd": 10,
          "score": 9.0,
          "fluency": "BAIK",
          "notes": "Hafalan lancar"
        }
      ]
    }
  }
}
```

---

### 2.5 Detail Nilai Per Mata Pelajaran

**Endpoint:** `GET /api/v1/portal/students/{student}/subjects/{subject}/grade-details`

**Headers:**
```
Authorization: Bearer {token}
X-Tenant-Domain: {tenant-slug}
```

**Response Sukses (200):**
```json
{
  "success": true,
  "message": "Berhasil memuat rincian nilai",
  "data": {
    "details": {
      "tugas": [
        {
          "id": 1,
          "title": "Tugas 1",
          "score": 85.0,
          "weight": 1.0,
          "date": "2026-04-10",
          "note": "Dikerjakan dengan baik"
        }
      ],
      "harian": [
        {
          "id": 2,
          "title": "UH 1",
          "score": 80.0,
          "weight": 1.0,
          "date": "2026-04-15",
          "note": null
        }
      ],
      "uts": [
        {
          "id": 3,
          "title": "UTS Genap",
          "score": 82.0,
          "weight": 1.0,
          "date": "2026-05-10",
          "note": null
        }
      ],
      "uas": [],
      "akhir": []
    },
    "averages": {
      "tugas": 85.0,
      "harian": 80.0,
      "uts": 82.0,
      "uas": null,
      "akhir": null
    },
    "hasData": true
  }
}
```

---

## 3. Alur Integrasi Frontend

### 3.1 Flow Autentikasi Wali Murid

```
1. User input email + password
2. POST /api/v1/auth/parent-login
3. Simpan token di localStorage/secureStorage
4. Simpan daftar students di state
5. Redirect ke halaman pilih anak (jika > 1) atau langsung dashboard
```

### 3.2 Flow Autentikasi Siswa

```
1. User input NIS + password
2. POST /api/v1/auth/student-login
3. Simpan token di localStorage/secureStorage
4. Simpan data student di state
5. Redirect ke dashboard siswa
```

### 3.3 Flow Load Dashboard

```
1. Ambil student_id dari state/route params
2. GET /api/v1/portal/students/{student_id}/dashboard?gradeType=PENGETAHUAN
3. Cache response di state (React Query / SWR / Zustand)
4. Tampilkan data di UI
5. Refresh on pull-to-refresh atau tab change
```

---

## 4. Error Handling

### 4.1 Error Codes

| Code | HTTP Status | Deskripsi | Aksi Frontend |
|------|-------------|-----------|---------------|
| `INVALID_CREDENTIALS` | 401 | Email/password/NIS salah | Tampilkan pesan error di form |
| `STUDENT_NOT_FOUND` | 444 | NIS tidak terdaftar | Tampilkan pesan error di form |
| `STUDENT_NOT_ACTIVATED` | 403 | Akun siswa belum diaktivasi | Tampilkan pesan hubungi TU |
| `USER_SUSPENDED` | 403 | Akun wali dinonaktifkan | Tampilkan pesan hubungi admin |
| `MODULE_INACTIVE` | 403 | Modul tidak aktif untuk tenant | Tampilkan pesan fitur tidak tersedia |
| `FORBIDDEN_OWNERSHIP` | 403 | Bukan wali dari siswa tersebut | Redirect ke halaman error |
| `TOKEN_EXPIRED` | 401 | Token sudah expired | Auto logout, redirect ke login |

### 4.2 Contoh Error Response

```json
{
  "success": false,
  "message": "Anda tidak memiliki akses ke data siswa ini.",
  "code": "FORBIDDEN_OWNERSHIP"
}
```

---

## 5. Konfigurasi untuk Frontend

### 5.1 Environment Variables

Buat file `.env.local` di project `simt-portalortu`:

```env
NEXT_PUBLIC_API_BASE_URL=http://localhost:8000/api
NEXT_PUBLIC_DEFAULT_TENANT=mts-alhikmah
```

### 5.2 Axios/Fetch Configuration

```typescript
// lib/api.ts
import axios from 'axios';

const api = axios.create({
  baseURL: process.env.NEXT_PUBLIC_API_BASE_URL,
  headers: {
    'Content-Type': 'application/json',
  },
});

// Interceptor untuk menambahkan X-Tenant-Domain
api.interceptors.request.use((config) => {
  const tenant = localStorage.getItem('tenant') || process.env.NEXT_PUBLIC_DEFAULT_TENANT;
  config.headers['X-Tenant-Domain'] = tenant;
  
  const token = localStorage.getItem('token');
  if (token) {
    config.headers['Authorization'] = `Bearer ${token}`;
  }
  
  return config;
});

// Interceptor untuk handle error
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Token expired, logout
      localStorage.removeItem('token');
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
```

### 5.3 API Service Functions

```typescript
// services/authService.ts
import api from '@/lib/api';

export const authService = {
  parentLogin: (email: string, password: string) =>
    api.post('/v1/auth/parent-login', { email, password }),
  
  studentLogin: (nis: string, password: string) =>
    api.post('/v1/auth/student-login', { nis, password }),
  
  logout: () => api.post('/v1/logout'),
  
  getMe: () => api.get('/v1/me'),
  
  getChildren: () => api.get('/v1/me/children'),
};

// services/dashboardService.ts
import api from '@/lib/api';

export const dashboardService = {
  getParentDashboard: (studentId: number, gradeType = 'PENGETAHUAN') =>
    api.get(`/v1/portal/students/${studentId}/dashboard`, { 
      params: { gradeType } 
    }),
  
  getStudentDashboard: (studentId: number, gradeType = 'PENGETAHUAN') =>
    api.get(`/v1/portal/students/${studentId}/student-dashboard`, { 
      params: { gradeType } 
    }),
  
  getGradeDetails: (studentId: number, subjectId: number) =>
    api.get(`/v1/portal/students/${studentId}/subjects/${subjectId}/grade-details`),
};
```

---

## 6. Middleware & Security

### 6.1 Middleware Stack

Setiap request ke `/api/v1/portal/*` melewati middleware:

1. **auth:sanctum** - Validasi token Bearer
2. **IdentifyTenant** - Identifikasi tenant dari header `X-Tenant-Domain`
3. **check.tenant.access** - Verifikasi user memiliki akses ke tenant

### 6.2 Ownership Check

Controller `PortalOrtuApiController` memiliki method `checkAccess()` yang memverifikasi:

- **Untuk Wali (User):** User adalah wali dari siswa tersebut (via `guardianStudents` pivot)
- **Untuk Siswa:** User adalah siswa yang sama (tidak bisa akses data siswa lain)

Jika gagal, response 403 dengan code `FORBIDDEN_OWNERSHIP`.

---

## 7. Performance Considerations

### 7.1 Single Request vs Multiple Requests

**REKOMENDASI:** Gunakan endpoint dashboard (single request) untuk load semua data.

**Keuntungan:**
- ✅ 1 request HTTP vs 3-4 request terpisah
- ✅ Data konsisten dalam 1 transaksi database
- ✅ Network latency minimal
- ✅ Cache lebih mudah di frontend

**Struktur data yang sudah lengkap:**
```
Dashboard Response
├── student (profile)
├── attendanceSummary (presensi)
├── grades (nilai)
├── payments (SPP)
├── announcements (pengumuman)
├── schedules (jadwal - student-dashboard only)
├── violations (pelanggaran - student-dashboard only)
├── achievements (prestasi - student-dashboard only)
└── tahfiz (hafalan - student-dashboard only)
```

### 7.2 Caching Strategy

```typescript
// Menggunakan React Query
import { useQuery } from '@tanstack/react-query';

export function useStudentDashboard(studentId: number) {
  return useQuery({
    queryKey: ['dashboard', studentId],
    queryFn: () => dashboardService.getParentDashboard(studentId),
    staleTime: 5 * 60 * 1000, // 5 menit
    cacheTime: 10 * 60 * 1000, // 10 menit
  });
}
```

---

## 8. Testing

### 8.1 Test Suite yang Tersedia

Backend memiliki test suite lengkap di `tests/Feature/PortalOrtuApiTest.php`:

```php
// Test yang sudah ada:
- test_student_can_login_with_correct_credentials()
- test_student_login_fails_with_incorrect_password()
- test_parent_can_login_with_correct_credentials()
- test_parent_can_access_dashboard_of_their_child()
- test_parent_cannot_access_dashboard_of_other_student()
- test_student_can_access_student_dashboard()
- test_student_cannot_access_other_student_dashboard()
- test_grade_details_endpoint()
```

### 8.2 Menjalankan Test

```bash
cd simt-backend
php artisan test --filter=PortalOrtuApiTest
```

---

## 9. Checklist Kesiapan Frontend

Gunakan checklist ini untuk memastikan frontend `simt-portalortu` siap integrasi:

### 9.1 Setup Awal
- [ ] Clone repo `simt-portalortu`
- [ ] Setup environment variables (API_BASE_URL, DEFAULT_TENANT)
- [ ] Install dependencies (`npm install` atau `yarn`)
- [ ] Setup API client (axios/fetch dengan interceptors)

### 9.2 Autentikasi
- [ ] Halaman login wali murid (email + password)
- [ ] Halaman login siswa (NIS + password)
- [ ] Handle error responses (INVALID_CREDENTIALS, STUDENT_NOT_ACTIVATED, dll)
- [ ] Simpan token di secure storage
- [ ] Implementasi logout (revoke token)

### 9.3 Dashboard
- [ ] Halaman pilih anak (untuk wali dengan > 1 anak)
- [ ] Dashboard wali murid dengan tabs:
  - [ ] Ringkasan presensi
  - [ ] Daftar nilai dengan filter tipe
  - [ ] Daftar pembayaran SPP
  - [ ] Pengumuman
- [ ] Dashboard siswa dengan tabs tambahan:
  - [ ] Jadwal pelajaran
  - [ ] Pelanggaran
  - [ ] Prestasi
  - [ ] Progres Tahfiz
- [ ] Detail nilai per mata pelajaran

### 9.4 UX/UI
- [ ] Pull-to-refresh untuk reload data
- [ ] Loading states (skeleton/shimmer)
- [ ] Error states dengan retry button
- [ ] Empty states (belum ada data)
- [ ] Responsive design (mobile-first)

### 9.5 Security
- [ ] Token disimpan di secure storage (bukan localStorage biasa untuk production)
- [ ] Auto-logout saat token expired
- [ ] Clear token saat logout manual

---

## 10. Known Limitations & Future Work

### 10.1 Current Limitations

1. **Endpoint granular belum terdaftar** - Controller sudah ada tapi belum di-route
   - `/api/v1/students/{student}/attendance`
   - `/api/v1/students/{student}/bills`
   
   **Solusi:** Gunakan endpoint dashboard yang sudah lengkap

2. **PortalOrtu frontend belum terintegrasi** - Repo ada tapi masih standalone dengan database Prisma terpisah

3. **Tidak ada refresh token** - Token Sanctum 30 hari, tidak ada mekanisme refresh

### 10.2 Future Work (Post-MVP)

1. Registrasi endpoint granular jika dibutuhkan
2. Implementasi refresh token mechanism
3. Real-time updates via WebSocket (untuk presensi live)
4. Push notification untuk pengumuman/tagihan baru
5. Offline mode dengan local database sync

---

## 11. Referensi

- **Backend Repo:** `simt-backend` (Laravel 11)
- **Controller:** `Modules\Core\app\Http\Controllers\PortalOrtuApiController.php`
- **Routes:** `routes/api.php`
- **Tests:** `tests\Feature\PortalOrtuApiTest.php`
- **PRD:** `DEV_DOCS\docs_sim\04_prd_sim_mts.md`
- **Audit Report:** `DEV_DOCS\docs_sim\99a_ARENA_AI_AUDIT_FINAL_3REPO.md`

---

**Dokumen ini dibuat oleh:** Kiro AI Agent  
**Tanggal:** 16 Juni 2026  
**Versi:** 1.0
