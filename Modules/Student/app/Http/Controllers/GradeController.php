<?php

namespace Modules\Student\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Grade;
use App\Models\Student;
use App\Models\Subject;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GradeController extends Controller
{
    public function index(Request $request): View
    {
        $query = Grade::with(['student.classes', 'subject', 'teacher']);

        if ($request->filled('student_id')) {
            $query->where('student_id', $request->input('student_id'));
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->input('subject_id'));
        }

        if ($request->filled('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('class_id')) {
            $studentIds = Student::whereHas('classes', fn ($q) => $q->where('class_id', $request->input('class_id')))->pluck('id');
            $query->whereIn('student_id', $studentIds);
        }

        $grades = $query->orderBy('created_at', 'desc')->paginate(30)->withQueryString();
        $classrooms = SchoolClass::orderBy('grade')->orderBy('name')->get();
        $subjects = Subject::orderBy('name')->get();
        $gradeTypes = Grade::select('type')->distinct()->pluck('type');

        return view('grades.index', compact('grades', 'classrooms', 'subjects', 'gradeTypes'));
    }

    public function create(Request $request): View
    {
        $subjectId = $request->input('subject_id');
        $classId = $request->input('class_id');

        $classrooms = SchoolClass::with('schoolYear')->orderBy('grade')->orderBy('name')->get();

        $selectedClassroom = $classId ? SchoolClass::find($classId) : $classrooms->first();

        $subjects = $selectedClassroom
            ? Subject::where('school_class_id', $selectedClassroom->id)->orderBy('name')->get()
            : collect();

        $selectedSubject = $subjectId ? Subject::find($subjectId) : $subjects->first();

        $students = $selectedClassroom
            ? Student::whereHas('classes', fn ($q) => $q->where('class_id', $selectedClassroom->id))->orderBy('name')->get()
            : collect();

        $type = $request->input('type', 'UH1');

        if ($selectedSubject && $students->isNotEmpty()) {
            $existingGrades = Grade::where('subject_id', $selectedSubject->id)
                ->where('type', $type)
                ->whereIn('student_id', $students->pluck('id'))
                ->get()
                ->keyBy('student_id');

            $students = $students->map(function ($student) use ($existingGrades) {
                $student->existingGrade = $existingGrades->get($student->id);
                return $student;
            });
        }

        return view('grades.create', compact(
            'classrooms',
            'selectedClassroom',
            'subjects',
            'selectedSubject',
            'students',
            'type'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject_id' => 'required|exists:subjects,id',
            'type' => 'required|in:UH1,UH2,UH3,UH4,UH5,UH6,UTS,UAS,TUGAS1,TUGAS2,TUGAS3,PRAKTIK,SIKAP',
            'grades' => 'required|array',
            'grades.*.student_id' => 'required|exists:students,id',
            'grades.*.score' => 'required|numeric|min:0|max:100',
            'grades.*.description' => 'nullable|string|max:255',
        ]);

        $tenantId = auth()->user()->tenant_id;
        $teacherId = auth()->id();
        $subjectId = $validated['subject_id'];
        $type = $validated['type'];
        $created = 0;
        $updated = 0;

        foreach ($validated['grades'] as $gradeData) {
            $data = [
                'tenant_id' => $tenantId,
                'student_id' => $gradeData['student_id'],
                'subject_id' => $subjectId,
                'teacher_id' => $teacherId,
                'type' => $type,
                'score' => $gradeData['score'],
                'description' => $gradeData['description'] ?? null,
            ];

            $existing = Grade::where('tenant_id', $tenantId)
                ->where('student_id', $data['student_id'])
                ->where('subject_id', $subjectId)
                ->where('type', $type)
                ->first();

            if ($existing) {
                $existing->update($data);
                $updated++;
            } else {
                Grade::create($data);
                $created++;
            }
        }

        return redirect()->route('grades.index', ['subject_id' => $subjectId])
            ->with('success', "Nilai berhasil disimpan. {$created} baru, {$updated} diperbarui.");
    }

    public function show(Grade $grade): View
    {
        $grade->load(['student.classes', 'subject', 'teacher']);

        return view('grades.show', compact('grade'));
    }

    public function update(Request $request, Grade $grade): RedirectResponse
    {
        $validated = $request->validate([
            'score' => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:255',
        ]);

        $grade->update($validated);

        return redirect()->back()
            ->with('success', 'Nilai berhasil diperbarui.');
    }

    public function rapor(Request $request)
    {
        $studentId = $request->input('student_id');
        $tenantId = auth()->user()->tenant_id;

        $students = Student::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->with('classes')
            ->orderBy('name')
            ->get();

        $student = $studentId ? Student::find($studentId) : null;

        if (!$student && $students->count() > 0) {
            $student = $students->first();
            $studentId = $student->id;
        }

        if (!$student) {
            return view('grades.rapor', compact('students', 'student'))
                ->with('info', 'Belum ada data siswa.');
        }

        $student->load('classes.teacher');
        $currentClass = $student->currentClass();

        if (!$currentClass) {
            $raporData = collect();
        } else {
            $subjects = Subject::where('tenant_id', $tenantId)
                ->where('school_class_id', $currentClass->id)
                ->with(['grades' => function ($q) use ($studentId) {
                    $q->where('student_id', $studentId);
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
                    'subject' => $subject,
                    'uh_average' => round($uhAverage, 1),
                    'tugas_average' => round($tugasAverage, 1),
                    'uts' => $utsScore,
                    'uas' => $uasScore,
                    'praktik' => $praktikScore,
                    'sikap' => $sikapScore,
                    'pengetahuan' => round($pengetahuan, 1),
                    'keterampilan' => round($keterampilan, 1),
                    'predicate_pengetahuan' => $this->getPredicate($pengetahuan),
                    'predicate_keterampilan' => $this->getPredicate($keterampilan),
                ];
            });
        }

        // Attendance summary can be retrieved from student's attendances relations
        $attendanceSummary = [
            'hadir' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'H')->count(),
            'sakit' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'S')->count(),
            'izin' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'I')->count(),
            'alpha' => $student->attendances()->whereMonth('date', now()->month)->whereYear('date', now()->year)->where('status', 'A')->count(),
        ];

        if ($request->input('export') === 'pdf') {
            $pdf = Pdf::loadView('grades.rapor-pdf', compact('student', 'raporData', 'attendanceSummary'));
            $pdf->setPaper('a4', 'portrait');
            return $pdf->stream("rapor-{$student->nis}-" . now()->format('Y-m-d') . '.pdf');
        }

        return view('grades.rapor', compact('students', 'student', 'raporData', 'attendanceSummary'));
    }

    protected function getPredicate(float $score): string
    {
        if ($score >= 93) return 'A';
        if ($score >= 84) return 'B';
        if ($score >= 75) return 'C';
        return 'D';
    }
}
