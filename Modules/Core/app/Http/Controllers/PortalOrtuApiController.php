<?php

namespace Modules\Core\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\GradeDetail;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Attendance;
use App\Models\Bill;
use App\Models\Announcement;
use App\Models\Schedule;
use App\Models\StudentViolation;
use App\Models\StudentAchievement;
use App\Models\TahfizRecord;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class PortalOrtuApiController extends Controller
{
    /**
     * Helper to verify parent's ownership of student
     */
    protected function checkAccess(Request $request, Student $student): ?JsonResponse
    {
        $user = $request->user();

        // If authenticated as User (parent/wali)
        if ($user instanceof User) {
            if ($user->hasRole('wali') && !$user->guardianStudents()->where('student_id', $student->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke data siswa ini.',
                    'code' => 'FORBIDDEN_OWNERSHIP',
                ], 403);
            }
        }
        
        // If authenticated as Student
        if ($user instanceof Student) {
            if ($user->id !== $student->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke data siswa lain.',
                    'code' => 'FORBIDDEN_OWNERSHIP',
                ], 403);
            }
        }

        return null;
    }

    /**
     * POST /api/v1/auth/student-login
     */
    public function studentLogin(Request $request): JsonResponse
    {
        $request->validate([
            'nis' => 'required|string',
            'password' => 'required|string',
        ]);

        $student = Student::where('nis', $request->input('nis'))
            ->where('status', 'active')
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'NIS tidak terdaftar atau siswa tidak aktif.',
                'code' => 'STUDENT_NOT_FOUND',
            ], 444);
        }

        if (!$student->student_password) {
            return response()->json([
                'success' => false,
                'message' => 'Akun siswa belum diaktifkan. Hubungi TU.',
                'code' => 'STUDENT_NOT_ACTIVATED',
            ], 403);
        }

        // Check password (plain text check for MVP compatibility, fallback to Hash if hashed)
        $passwordMatches = false;
        if ($student->student_password === $request->input('password')) {
            $passwordMatches = true;
        } else {
            // Only try Hash::check if it looks like a bcrypt hash (starts with $2)
            if (str_starts_with($student->student_password, '$2')) {
                try {
                    $passwordMatches = Hash::check($request->input('password'), $student->student_password);
                } catch (\Throwable $e) {
                    $passwordMatches = false;
                }
            }
        }

        if (!$passwordMatches) {
            return response()->json([
                'success' => false,
                'message' => 'Password salah.',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        $tenant = $student->tenant;
        $currentClass = $student->currentClass();

        $token = $student->createToken('student-token', ['*'], now()->addDays(30))->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'name' => $student->name,
                    'nis' => $student->nis,
                    'nisn' => $student->nisn,
                    'gender' => $student->gender,
                    'classroom' => $currentClass?->name,
                    'level' => $currentClass?->grade,
                    'tenant' => [
                        'name' => $tenant->name,
                        'slug' => $tenant->domain,
                    ],
                    'birthPlace' => $student->birth_place,
                    'birthDate' => $student->birth_date?->toDateString(),
                    'address' => $student->address,
                    'photo' => $student->photo,
                ],
                'token' => $token,
            ],
        ]);
    }

    /**
     * POST /api/v1/auth/parent-login
     */
    public function parentLogin(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::withoutGlobalScope('tenant')
            ->where('email', $request->input('email'))
            ->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Kredensial tidak valid.',
                'code' => 'INVALID_CREDENTIALS',
            ], 401);
        }

        if (!$user->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Akun wali murid dinonaktifkan.',
                'code' => 'USER_SUSPENDED',
            ], 403);
        }

        $token = $user->createToken('parent-token', ['*'], now()->addDays(30))->plainTextToken;

        // Fetch children
        $children = $user->guardianStudents()
            ->where('status', 'active')
            ->get();

        $studentsList = $children->map(function ($s) {
            $currentClass = $s->currentClass();
            return [
                'id' => $s->id,
                'name' => $s->name,
                'nis' => $s->nis,
                'classroom' => $currentClass?->name,
                'level' => $currentClass?->grade,
                'tenant' => [
                    'name' => $s->tenant->name,
                    'slug' => $s->tenant->domain,
                ],
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Login berhasil',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'students' => $studentsList,
                'token' => $token,
            ],
        ]);
    }

    /**
     * GET /api/v1/portal/students/{student}/dashboard
     */
    public function dashboard(Request $request, Student $student): JsonResponse
    {
        if ($err = $this->checkAccess($request, $student)) {
            return $err;
        }

        $gradeType = $request->query('gradeType', 'PENGETAHUAN');

        // Fetch Dashboard Data
        $data = $this->getDashboardPayload($student, $gradeType, false);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat dashboard',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/portal/students/{student}/student-dashboard
     */
    public function studentDashboard(Request $request, Student $student): JsonResponse
    {
        if ($err = $this->checkAccess($request, $student)) {
            return $err;
        }

        $gradeType = $request->query('gradeType', 'PENGETAHUAN');

        // Fetch Extended Dashboard Data
        $data = $this->getDashboardPayload($student, $gradeType, true);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat dashboard siswa',
            'data' => $data,
        ]);
    }

    /**
     * GET /api/v1/portal/students/{student}/subjects/{subject}/grade-details
     */
    public function gradeDetails(Request $request, Student $student, Subject $subject): JsonResponse
    {
        if ($err = $this->checkAccess($request, $student)) {
            return $err;
        }

        // Fetch grade details
        $details = GradeDetail::where('student_id', $student->id)
            ->where('subject_id', $subject->id)
            ->orderBy('category', 'asc')
            ->orderBy('date', 'asc')
            ->get();

        $grouped = [
            'tugas' => $details->filter(fn($d) => $d->category === 'TUGAS')->map(fn($d) => [
                'id' => $d->id, 'title' => $d->title, 'score' => (float)$d->score, 'weight' => (float)$d->weight,
                'date' => $d->date?->toDateString(), 'note' => $d->note,
            ])->values()->all(),
            'harian' => $details->filter(fn($d) => $d->category === 'HARIAN')->map(fn($d) => [
                'id' => $d->id, 'title' => $d->title, 'score' => (float)$d->score, 'weight' => (float)$d->weight,
                'date' => $d->date?->toDateString(), 'note' => $d->note,
            ])->values()->all(),
            'uts' => $details->filter(fn($d) => $d->category === 'UTS')->map(fn($d) => [
                'id' => $d->id, 'title' => $d->title, 'score' => (float)$d->score, 'weight' => (float)$d->weight,
                'date' => $d->date?->toDateString(), 'note' => $d->note,
            ])->values()->all(),
            'uas' => $details->filter(fn($d) => $d->category === 'UAS')->map(fn($d) => [
                'id' => $d->id, 'title' => $d->title, 'score' => (float)$d->score, 'weight' => (float)$d->weight,
                'date' => $d->date?->toDateString(), 'note' => $d->note,
            ])->values()->all(),
            'akhir' => $details->filter(fn($d) => $d->category === 'AKHIR')->map(fn($d) => [
                'id' => $d->id, 'title' => $d->title, 'score' => (float)$d->score, 'weight' => (float)$d->weight,
                'date' => $d->date?->toDateString(), 'note' => $d->note,
            ])->values()->all(),
        ];

        // Averages
        $avg = function ($arr) {
            if (empty($arr)) return null;
            $sum = array_reduce($arr, fn($carry, $item) => $carry + $item['score'], 0);
            return round($sum / count($arr), 1);
        };

        $averages = [
            'tugas' => $avg($grouped['tugas']),
            'harian' => $avg($grouped['harian']),
            'uts' => $avg($grouped['uts']),
            'uas' => $avg($grouped['uas']),
            'akhir' => $avg($grouped['akhir']),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat rincian nilai',
            'data' => [
                'details' => $grouped,
                'averages' => $averages,
                'hasData' => $details->isNotEmpty(),
            ]
        ]);
    }

    /**
     * Shared logic to construct dashboard payload
     */
    protected function getDashboardPayload(Student $student, string $gradeType, bool $extended): array
    {
        $tenant = $student->tenant;
        $currentClass = $student->currentClass();
        $waliKelas = $currentClass?->teacher;

        // 1. Profile
        $profile = [
            'id' => $student->id,
            'name' => $student->name,
            'nis' => $student->nis,
            'nisn' => $student->nisn,
            'gender' => $student->gender,
            'birthPlace' => $student->birth_place,
            'birthDate' => $student->birth_date?->toDateString(),
            'address' => $student->address,
            'photo' => $student->photo,
            'fatherName' => $student->father_name ?? $student->guardians()->wherePivot('relation', 'ayah')->first()?->name,
            'fatherPhone' => $student->father_phone ?? $student->guardians()->wherePivot('relation', 'ayah')->first()?->phone,
            'motherName' => $student->mother_name ?? $student->guardians()->wherePivot('relation', 'ibu')->first()?->name,
            'motherPhone' => $student->mother_phone ?? $student->guardians()->wherePivot('relation', 'ibu')->first()?->phone,
            'parentEmail' => $student->parent_email ?? $student->guardians()->first()?->email,
            'classroom' => $currentClass ? [
                'id' => $currentClass->id,
                'name' => $currentClass->name,
                'level' => (int)$currentClass->grade,
                'capacity' => 36,
                'academicYear' => $currentClass->schoolYear ? [
                    'id' => $currentClass->schoolYear->id,
                    'name' => $currentClass->schoolYear->name,
                    'semester' => 2, // Genap
                    'isActive' => (bool)$currentClass->schoolYear->is_active,
                ] : null,
                'waliKelas' => $waliKelas ? [
                    'name' => $waliKelas->name,
                    'phone' => $waliKelas->phone,
                ] : null,
            ] : null,
            'tenant' => [
                'name' => $tenant->name,
                'slug' => $tenant->domain,
                'logo' => null, // Placeholder or custom domain logo
            ]
        ];

        // 2. Attendance Summary (Smart Period Fallback)
        $attendances = Attendance::where('student_id', $student->id)
            ->orderBy('date', 'desc')
            ->get();

        $attendanceSummary = [
            'hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpha' => 0, 'total' => 0,
            'recent' => [], 'daily' => [], 'periodLabel' => 'Belum ada data', 'hasData' => false
        ];

        if ($attendances->isNotEmpty()) {
            $now = Carbon::now();
            $currentMonth = $now->month;
            $currentYear = $now->year;

            $monthAttendances = $attendances->filter(fn($a) => Carbon::parse($a->date)->month === $currentMonth && Carbon::parse($a->date)->year === $currentYear);

            if ($monthAttendances->isEmpty()) {
                // Fallback to latest month with data
                $latestDate = Carbon::parse($attendances->first()->date);
                $fallbackMonth = $latestDate->month;
                $fallbackYear = $latestDate->year;
                $monthAttendances = $attendances->filter(fn($a) => Carbon::parse($a->date)->month === $fallbackMonth && Carbon::parse($a->date)->year === $fallbackYear);
            }

            $hadir = $monthAttendances->filter(fn($a) => in_array($a->status, ['H', 'T']))->count();
            $sakit = $monthAttendances->filter(fn($a) => $a->status === 'S')->count();
            $izin = $monthAttendances->filter(fn($a) => $a->status === 'I')->count();
            $alpha = $monthAttendances->filter(fn($a) => $a->status === 'A')->count();

            $monthNames = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
            $sampleDate = Carbon::parse($monthAttendances->first()->date);
            $periodLabel = "Bulan " . $monthNames[$sampleDate->month] . " " . $sampleDate->year;

            $recent = $monthAttendances->take(10)->map(fn($a) => [
                'id' => $a->id,
                'date' => Carbon::parse($a->date)->toDateString(),
                'status' => match($a->status) {
                    'H', 'T' => 'HADIR',
                    'S' => 'SAKIT',
                    'I' => 'IZIN',
                    'A' => 'ALPHA',
                    default => 'HADIR'
                },
                'timeIn' => $a->arrival_time,
                'timeOut' => null,
                'notes' => $a->notes,
            ])->values()->all();

            $attendanceSummary = [
                'hadir' => $hadir,
                'sakit' => $sakit,
                'izin' => $izin,
                'alpha' => $alpha,
                'total' => $monthAttendances->count(),
                'recent' => $recent,
                'daily' => $recent,
                'periodLabel' => $periodLabel,
                'hasData' => true,
            ];
        }

        // 3. Grades Calculation (PENGETAHUAN / KETERAMPILAN / UTS / UAS / SIKAP / RAPOR)
        // Retrieve all subjects in the class
        $subjects = $currentClass ? Subject::where('school_class_id', $currentClass->id)->get() : collect();
        $rawGrades = Grade::where('student_id', $student->id)->get();

        $gradesList = [];
        $availableTypes = ['PENGETAHUAN', 'KETERAMPILAN', 'UTS', 'UAS', 'SIKAP'];

        foreach ($subjects as $subj) {
            $subjGrades = $rawGrades->where('subject_id', $subj->id);

            // Rata-rata UH
            $uhGrades = $subjGrades->filter(fn($g) => str_starts_with($g->type, 'UH'));
            $uhAvg = $uhGrades->isNotEmpty() ? $uhGrades->avg('score') : 0;

            // Rata-rata Tugas
            $tugasGrades = $subjGrades->filter(fn($g) => str_starts_with($g->type, 'TUGAS'));
            $tugasAvg = $tugasGrades->isNotEmpty() ? $tugasGrades->avg('score') : 0;

            $utsScore = $subjGrades->firstWhere('type', 'UTS')?->score ?? 0;
            $uasScore = $subjGrades->firstWhere('type', 'UAS')?->score ?? 0;
            $praktikScore = $subjGrades->firstWhere('type', 'PRAKTIK')?->score ?? 0;
            $sikapScore = $subjGrades->firstWhere('type', 'SIKAP')?->score ?? 0;

            // Compute dynamic Rapor/Pengetahuan/Keterampilan
            $pengetahuanVal = ($uhAvg * 0.25) + ($tugasAvg * 0.15) + ($utsScore * 0.30) + ($uasScore * 0.30);
            $keterampilanVal = ($uhAvg * 0.25) + ($praktikScore * 0.40) + ($tugasAvg * 0.10) + ($uasScore * 0.25);

            $selectedScore = 0;
            if ($gradeType === 'PENGETAHUAN') $selectedScore = $pengetahuanVal;
            elseif ($gradeType === 'KETERAMPILAN') $selectedScore = $keterampilanVal;
            elseif ($gradeType === 'UTS') $selectedScore = $utsScore;
            elseif ($gradeType === 'UAS') $selectedScore = $uasScore;
            elseif ($gradeType === 'SIKAP') $selectedScore = $sikapScore;

            $gradesList[] = [
                'id' => $subj->id,
                'subjectId' => $subj->id,
                'subject' => [
                    'id' => $subj->id,
                    'name' => $subj->name,
                    'code' => $subj->code,
                    'category' => 'UMUM',
                ],
                'type' => $gradeType,
                'score' => round($selectedScore, 1),
                'kkm' => 75,
                'notes' => $selectedScore >= 75 ? 'Tuntas' : 'Perlu Remedial',
                'teacher' => [
                    'name' => $waliKelas?->name ?? 'Guru Mapel',
                ]
            ];
        }

        $avgGrade = count($gradesList) > 0 ? array_sum(array_column($gradesList, 'score')) / count($gradesList) : 0;

        // Calculate below KKM (using Pengetahuan as base KKM check like Next.js does)
        $pengetahuanScores = [];
        foreach ($subjects as $subj) {
            $subjGrades = $rawGrades->where('subject_id', $subj->id);
            $uhAvg = $subjGrades->filter(fn($g) => str_starts_with($g->type, 'UH'))->avg('score') ?? 0;
            $tugasAvg = $subjGrades->filter(fn($g) => str_starts_with($g->type, 'TUGAS'))->avg('score') ?? 0;
            $utsScore = $subjGrades->firstWhere('type', 'UTS')?->score ?? 0;
            $uasScore = $subjGrades->firstWhere('type', 'UAS')?->score ?? 0;
            $pengetahuanScores[] = ($uhAvg * 0.25) + ($tugasAvg * 0.15) + ($utsScore * 0.30) + ($uasScore * 0.30);
        }
        $belowKKMCount = count(array_filter($pengetahuanScores, fn($s) => $s < 75));
        $pengetahuanAvg = count($pengetahuanScores) > 0 ? array_sum($pengetahuanScores) / count($pengetahuanScores) : 0;

        $gradesSummary = [
            'list' => $gradesList,
            'average' => round($avgGrade, 1),
            'count' => count($gradesList),
            'activeType' => $gradeType,
            'availableTypes' => array_map(fn($t) => ['type' => $t, 'count' => count($gradesList)], $availableTypes),
            'hasData' => count($gradesList) > 0,
            'belowKKMCount' => $belowKKMCount,
            'pengetahuanAverage' => round($pengetahuanAvg, 1),
            'isAllTuntas' => $belowKKMCount === 0 && count($gradesList) > 0,
        ];

        // 4. Payments
        $bills = Bill::where('student_id', $student->id)
            ->where('component', 'SPP')
            ->orderBy('period', 'desc')
            ->get();

        $allPayments = $bills->map(fn($b) => [
            'id' => $b->id,
            'type' => 'SPP',
            'amount' => (int)$b->amount,
            'month' => (int)substr($b->period, 5, 2),
            'year' => (int)substr($b->period, 0, 4),
            'status' => match($b->status) {
                'paid' => 'LUNAS',
                'partial' => 'SEBAGIAN',
                'unpaid' => 'BELUM_BAYAR',
                default => 'BELUM_BAYAR'
            },
            'paidAmount' => (int)$b->paid_amount,
            'paymentDate' => $b->payments()->orderBy('payment_date', 'desc')->first()?->payment_date,
            'paymentMethod' => $b->payments()->orderBy('payment_date', 'desc')->first()?->method,
            'notes' => null,
            'dueDate' => $b->due_date,
        ])->values()->all();

        $unpaidBills = array_filter($allPayments, fn($p) => $p['status'] !== 'LUNAS');
        $totalUnpaid = array_reduce($unpaidBills, fn($carry, $item) => $carry + ($item['amount'] - $item['paidAmount']), 0);
        $totalPaid = array_reduce($allPayments, fn($carry, $item) => $carry + $item['paidAmount'], 0);

        $paymentsSummary = [
            'all' => $allPayments,
            'unpaid' => array_values($unpaidBills),
            'totalUnpaid' => $totalUnpaid,
            'totalPaid' => $totalPaid,
            'hasData' => count($allPayments) > 0,
        ];

        // 5. Announcements
        $announcements = Announcement::where('published_at', '<=', now())
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->orderBy('is_pinned', 'desc')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get()
            ->map(fn($a) => [
                'id' => $a->id,
                'title' => $a->title,
                'content' => $a->content,
                'category' => strtoupper($a->category),
                'isPinned' => (bool)$a->is_pinned,
                'publishedAt' => $a->published_at,
                'expiresAt' => $a->expires_at,
                'createdBy' => ['name' => 'Admin Sekolah'],
            ])->values()->all();

        $payload = [
            'student' => $profile,
            'attendanceSummary' => $attendanceSummary,
            'grades' => $gradesSummary,
            'payments' => $paymentsSummary,
            'announcements' => $announcements,
        ];

        // 6. Extended Data for Student Portal
        if ($extended) {
            // Schedules
            $schedules = $currentClass ? Schedule::where('class_id', $currentClass->id)
                ->with(['subject', 'teacher'])
                ->get()
                ->map(fn($s) => [
                    'id' => $s->id,
                    'dayOfWeek' => (int)$s->day_of_week,
                    'startPeriod' => (int)$s->start_period,
                    'endPeriod' => (int)$s->end_period,
                    'subject' => [
                        'id' => $s->subject?->id,
                        'name' => $s->subject?->name,
                        'code' => $s->subject?->code,
                    ],
                    'teacher' => [
                        'id' => $s->teacher?->id,
                        'name' => $s->teacher?->name ?? 'Guru Pelajaran',
                        'phone' => $s->teacher?->phone,
                    ],
                    'classroom' => [
                        'name' => $currentClass->name,
                    ]
                ])->values()->all() : [];

            // Violations
            $violationsList = StudentViolation::where('student_id', $student->id)
                ->with('recorder')
                ->get();

            $violations = [
                'list' => $violationsList->map(fn($v) => [
                    'id' => $v->id,
                    'date' => $v->date?->toISOString(),
                    'category' => strtoupper($v->category),
                    'description' => $v->description,
                    'points' => (int)$v->points,
                    'action' => $v->action,
                    'handledBy' => ['name' => $v->recorder?->name ?? 'Staf Kesiswaan'],
                ])->values()->all(),
                'totalPoints' => (int)$violationsList->sum('points'),
                'count' => $violationsList->count(),
            ];

            // Achievements
            $achievementsList = StudentAchievement::where('student_id', $student->id)->get();

            $achievements = [
                'list' => $achievementsList->map(fn($a) => [
                    'id' => $a->id,
                    'date' => $a->date?->toISOString(),
                    'title' => $a->title,
                    'category' => strtoupper($a->category),
                    'level' => strtoupper($a->level),
                    'ranking' => $a->ranking,
                    'organizer' => $a->description, // Fallback to desc as organizer
                    'certificateUrl' => $a->certificate,
                    'notes' => $a->description,
                ])->values()->all(),
                'count' => $achievementsList->count(),
            ];

            // Tahfiz progress records
            $tahfizList = TahfizRecord::where('student_id', $student->id)
                ->with('recorder')
                ->orderBy('date', 'desc')
                ->get();

            $tahfiz = [
                'totalRecords' => $tahfizList->count(),
                'ziyadahCount' => $tahfizList->where('type', 'ziyadah')->count(),
                'murajaahCount' => $tahfizList->where('type', 'murajaah')->count(),
                'averageScore' => $tahfizList->isEmpty() ? 0 : round($tahfizList->avg('score'), 1),
                'surahMemorized' => $tahfizList->pluck('surah')->unique()->count(),
                'latestRecords' => $tahfizList->take(10)->map(fn($t) => [
                    'id' => $t->id,
                    'date' => $t->date?->toDateString(),
                    'type' => strtoupper($t->type),
                    'surah' => $t->surah,
                    'ayahStart' => (int)$t->ayah_start,
                    'ayahEnd' => (int)$t->ayah_end,
                    'score' => (float)$t->score,
                    'fluency' => strtoupper($t->fluency),
                    'notes' => $t->note,
                    'teacher' => ['name' => $t->recorder?->name ?? 'Guru Tahfiz'],
                ])->values()->all(),
            ];

            $payload['schedules'] = $schedules;
            $payload['violations'] = $violations;
            $payload['achievements'] = $achievements;
            $payload['tahfiz'] = $tahfiz;
        }

        return $payload;
    }
}
