<?php

namespace Modules\Akademik\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Grade;
use App\Models\SchoolYear;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AkademikApiController extends Controller
{
    /**
     * Helper for ownership check: Wali must own the student access
     */
    protected function checkAccess(Request $request, Student $student): ?JsonResponse
    {
        $user = $request->user();
        if ($user->hasRole('wali') && ! $user->guardianStudents()->where('student_id', $student->id)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses ke data akademik siswa ini.',
                'code' => 'FORBIDDEN_OWNERSHIP',
            ], 403);
        }
        return null;
    }

    /**
     * GET /api/v1/students/{student}/grades
     */
    public function grades(Request $request, Student $student): JsonResponse
    {
        if ($errorResponse = $this->checkAccess($request, $student)) {
            return $errorResponse;
        }

        $grades = Grade::where('student_id', $student->id)
            ->with(['subject', 'teacher'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat data nilai siswa',
            'data' => $grades->map(fn ($g) => [
                'id' => $g->id,
                'subject' => [
                    'id' => $g->subject?->id,
                    'name' => $g->subject?->name,
                    'code' => $g->subject?->code,
                ],
                'type' => $g->type,
                'type_label' => $g->getTypeLabel(),
                'score' => (float) $g->score,
                'predicate' => $g->getGradePredicate(),
                'description' => $g->description,
                'teacher' => [
                    'id' => $g->teacher?->id,
                    'name' => $g->teacher?->name,
                ],
                'created_at' => $g->created_at?->toDateTimeString(),
            ]),
        ]);
    }

    /**
     * GET /api/v1/students/{student}/rapor
     */
    public function rapor(Request $request, Student $student): JsonResponse
    {
        if ($errorResponse = $this->checkAccess($request, $student)) {
            return $errorResponse;
        }

        $tenantId = $student->tenant_id;
        $student->load('classes.teacher');
        $currentClass = $student->currentClass();

        if (!$currentClass) {
            return response()->json([
                'success' => true,
                'message' => 'Siswa belum terdaftar pada kelas aktif.',
                'data' => null,
            ]);
        }

        $subjects = Subject::where('tenant_id', $tenantId)
            ->where('school_class_id', $currentClass->id)
            ->with(['grades' => function ($q) use ($student) {
                $q->where('student_id', $student->id);
            }, 'teacher'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $raporData = $subjects->map(function ($subject) {
            $grades = $subject->grades;

            $uhGrades = $grades->filter(fn ($g) => str_starts_with($g->type, 'UH'));
            $tugasGrades = $grades->filter(fn ($g) => str_starts_with($g->type, 'TUGAS'));
            $uts = $grades->firstWhere('type', 'UTS');
            $uas = $grades->firstWhere('type', 'UAS');
            $praktik = $grades->firstWhere('type', 'PRAKTIK');
            $sikap = $grades->firstWhere('type', 'SIKAP');

            $uhAverage = $uhGrades->isNotEmpty() ? $uhGrades->avg('score') : 0;
            $tugasAverage = $tugasGrades->isNotEmpty() ? $tugasGrades->avg('score') : 0;
            $utsScore = $uts?->score ?? 0;
            $uasScore = $uas?->score ?? 0;
            $praktikScore = $praktik?->score ?? 0;
            $sikapScore = $sikap?->score ?? 0;

            $pengetahuan = ($uhAverage * 0.25) + ($tugasAverage * 0.15) + ($utsScore * 0.30) + ($uasScore * 0.30);
            $keterampilan = ($uhAverage * 0.25) + ($praktikScore * 0.40) + ($tugasAverage * 0.10) + ($uasScore * 0.25);

            return [
                'subject' => [
                    'id' => $subject->id,
                    'name' => $subject->name,
                    'code' => $subject->code,
                    'category' => $subject->category,
                    'teacher' => $subject->teacher ? [
                        'id' => $subject->teacher->id,
                        'name' => $subject->teacher->name,
                    ] : null,
                ],
                'grades_detail' => [
                    'uh_average' => round($uhAverage, 1),
                    'tugas_average' => round($tugasAverage, 1),
                    'uts' => (float) $utsScore,
                    'uas' => (float) $uasScore,
                    'praktik' => (float) $praktikScore,
                    'sikap' => (float) $sikapScore,
                ],
                'pengetahuan' => round($pengetahuan, 1),
                'keterampilan' => round($keterampilan, 1),
                'predicate_pengetahuan' => $this->getPredicate($pengetahuan),
                'predicate_keterampilan' => $this->getPredicate($keterampilan),
            ];
        });

        $attendanceSummary = [
            'hadir' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'H')->count(),
            'sakit' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'S')->count(),
            'izin' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'I')->count(),
            'alpha' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'A')->count(),
        ];

        return response()->json([
            'success' => true,
            'message' => 'Berhasil memuat data rapor digital',
            'data' => [
                'student' => [
                    'id' => $student->id,
                    'nis' => $student->nis,
                    'name' => $student->name,
                    'class' => $currentClass->name,
                    'grade' => $currentClass->grade,
                ],
                'rapor_grades' => $raporData,
                'attendance_summary' => $attendanceSummary,
            ],
        ]);
    }

    protected function getPredicate(float $score): string
    {
        if ($score >= 93) return 'A';
        if ($score >= 84) return 'B';
        if ($score >= 75) return 'C';
        return 'D';
    }
}
