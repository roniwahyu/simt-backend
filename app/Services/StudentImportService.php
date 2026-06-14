<?php

namespace App\Services;

use App\Jobs\SendWaNotification;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

/**
 * StudentImportService — Import siswa dari Excel (3 step wizard)
 *
 * Step 1: Upload → validasi → cache hasil 30 menit (by UUID token)
 * Step 2: Preview → tampilkan data + error per baris
 * Step 3: Commit → simpan dalam transaksi tunggal, skip baris error
 *
 * Format Excel:
 * | nis | nisn | nama | jenis_kelamin | tanggal_lahir | tempat_lahir | alamat | kelas | wali_phone | wali_nama | hubungan |
 */
class StudentImportService
{
    /**
     * Step 1: Validate uploaded Excel file
     * Returns [token, validRows, errorRows, summary]
     */
    public function validate(int $tenantId, $file): array
    {
        $rows = Excel::toArray([], $file);
        $data = $rows[0] ?? [];

        $headerRow = array_map('strtolower', array_map('trim', $data[0] ?? []));
        unset($data[0]); // Remove header row

        $validRows = [];
        $errorRows = [];
        $activeYear = SchoolYear::where('tenant_id', $tenantId)->where('is_active', true)->first();

        if (! $activeYear) {
            return [
                'token' => null,
                'validRows' => [],
                'errorRows' => [],
                'summary' => ['error' => 'Tidak ada Tahun Ajaran aktif. Buat tahun ajaran dulu.'],
            ];
        }

        foreach ($data as $idx => $row) {
            $lineNumber = $idx + 2; // +2 because header is row 1
            $row = array_pad($row, 11, null); // Ensure at least 11 columns

            $errors = [];

            // Map columns
            $nis = trim($row[0] ?? '');
            $nisn = trim($row[1] ?? '');
            $nama = trim($row[2] ?? '');
            $gender = strtoupper(trim($row[3] ?? ''));
            $birthDate = trim($row[4] ?? '');
            $birthPlace = trim($row[5] ?? '');
            $address = trim($row[6] ?? '');
            $className = trim($row[7] ?? '');
            $waliPhone = trim($row[8] ?? '');
            $waliName = trim($row[9] ?? '');
            $relation = trim($row[10] ?? 'ayah');

            // Validate required fields
            if (empty($nis)) $errors[] = 'NIS kosong';
            if (empty($nama)) $errors[] = 'Nama kosong';

            // Validate NIS unique per tenant
            if (!empty($nis) && Student::where('tenant_id', $tenantId)->where('nis', $nis)->exists()) {
                $errors[] = "NIS '{$nis}' sudah ada";
            }

            // Validate gender
            if (!empty($gender) && !in_array($gender, ['L', 'P'])) {
                $errors[] = "Jenis kelamin harus L/P, diberikan: '{$gender}'";
            }

            // Validate class exists
            $classId = null;
            if (!empty($className)) {
                $class = SchoolClass::where('tenant_id', $tenantId)
                    ->where('name', $className)
                    ->where('school_year_id', $activeYear->id)
                    ->first();
                if ($class) {
                    $classId = $class->id;
                } else {
                    $errors[] = "Kelas '{$className}' tidak ditemukan";
                }
            }

            // Normalize WA phone
            $normalizedPhone = $this->normalizePhone($waliPhone);

            if (empty($errors)) {
                $validRows[] = [
                    'nis' => $nis,
                    'nisn' => $nisn ?: null,
                    'name' => $nama,
                    'gender' => $gender ?: 'L',
                    'birth_date' => $birthDate ?: null,
                    'birth_place' => $birthPlace ?: null,
                    'address' => $address ?: null,
                    'class_id' => $classId,
                    'wali_phone' => $normalizedPhone,
                    'wali_name' => $waliName ?: null,
                    'relation' => $relation ?: 'ayah',
                    'line' => $lineNumber,
                ];
            } else {
                $errorRows[] = [
                    'line' => $lineNumber,
                    'data' => array_slice($row, 0, 8),
                    'errors' => $errors,
                ];
            }
        }

        // Cache results for 30 minutes
        $token = Str::uuid()->toString();
        Cache::put("import:{$token}", [
            'tenant_id' => $tenantId,
            'validRows' => $validRows,
            'school_year_id' => $activeYear->id,
        ], now()->addMinutes(30));

        return [
            'token' => $token,
            'validRows' => $validRows,
            'errorRows' => $errorRows,
            'summary' => [
                'total' => count($data),
                'valid' => count($validRows),
                'errors' => count($errorRows),
            ],
        ];
    }

    /**
     * Step 3: Commit validated rows to database
     * Returns [imported, skipped, waQueued]
     */
    public function commit(string $token): array
    {
        $cached = Cache::get("import:{$token}");

        if (! $cached) {
            return ['error' => 'Token import expired. Silakan upload ulang.'];
        }

        $tenantId = $cached['tenant_id'];
        $validRows = $cached['validRows'];
        $schoolYearId = $cached['school_year_id'];
        $imported = 0;
        $skipped = 0;
        $waQueued = 0;

        DB::transaction(function () use ($tenantId, $validRows, $schoolYearId, &$imported, &$skipped, &$waQueued) {
            foreach ($validRows as $row) {
                try {
                    // Create student
                    $student = Student::create([
                        'tenant_id' => $tenantId,
                        'nis' => $row['nis'],
                        'nisn' => $row['nisn'],
                        'name' => $row['name'],
                        'gender' => $row['gender'],
                        'birth_date' => $row['birth_date'],
                        'birth_place' => $row['birth_place'],
                        'address' => $row['address'],
                        'status' => 'active',
                    ]);

                    // Attach to class if provided
                    if ($row['class_id']) {
                        $student->classes()->syncWithoutDetaching([
                            $row['class_id'] => ['school_year_id' => $schoolYearId]
                        ]);
                    }

                    // Create guardian (wali) account if phone provided
                    if ($row['wali_phone']) {
                        $wali = User::firstOrCreate(
                            ['phone' => $row['wali_phone']],
                            [
                                'tenant_id' => $tenantId,
                                'name' => $row['wali_name'] ?? 'Wali ' . $row['name'],
                                'email' => 'wali_' . $row['nis'] . '@simt.local',
                                'password' => Hash::make('simt2026'),
                                'role_display' => 'wali',
                            ]
                        );
                        $wali->assignRole('wali');
                        $student->guardians()->syncWithoutDetaching([$wali->id => ['relation' => $row['relation']]]);

                        // Queue WA notification
                        SendWaNotification::dispatch(
                            $tenantId,
                            $wali->phone,
                            "Assalamu'alaikum. Akun SIMT MTs untuk monitoring anak Anda ({$row['name']}) telah dibuat. Login dengan no. HP ini di portal SIMT.",
                            'account_created'
                        );
                        $waQueued++;
                    }

                    $imported++;
                } catch (\Throwable $e) {
                    $skipped++;
                }
            }
        });

        // Remove cache after commit
        Cache::forget("import:{$token}");

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'waQueued' => $waQueued,
        ];
    }

    /**
     * Normalize phone number: 08xx → 628xx, +628 → 628
     */
    private function normalizePhone(string $phone): ?string
    {
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $phone);
        if (empty($cleaned)) return null;

        if (str_starts_with($cleaned, '+62')) {
            $cleaned = substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '08')) {
            $cleaned = '62' . substr($cleaned, 1);
        } elseif (str_starts_with($cleaned, '8') && strlen($cleaned) >= 10) {
            $cleaned = '62' . $cleaned;
        }

        if (preg_match('/^628\d{7,12}$/', $cleaned)) {
            return $cleaned;
        }

        return null;
    }
}
