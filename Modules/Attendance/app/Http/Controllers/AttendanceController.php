<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Jobs\SendWaNotification;
use App\Models\Attendance;
use App\Models\SchoolClass;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Attendance\Exports\AttendanceRecapExport;

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. General permission check
        if (! $user->can('view_attendance')) {
            abort(403, 'Anda tidak memiliki akses ke halaman presensi.');
        }

        // 2. Get authorized classes based on user role
        if ($user->hasRole('guru')) {
            // Teachers can only view/manage their assigned classes
            $classes = SchoolClass::with('schoolYear')->where('teacher_id', $user->id)->get();
        } else {
            // Admins, TU, and Kepala Madrasah can view all classes
            $classes = SchoolClass::with('schoolYear')->get();
        }

        $selectedClass = $request->input('class_id');
        $date = $request->input('date', now()->toDateString());

        $students = collect();
        if ($selectedClass) {
            // 3. Ensure the selected class is authorized for the current user
            if (! $classes->contains('id', $selectedClass)) {
                abort(403, 'Anda tidak memiliki akses ke data presensi kelas ini.');
            }

            $class = SchoolClass::with(['students' => fn ($q) => $q->where('status', 'active')])->find($selectedClass);
            if ($class) {
                $students = $class->students;
                $attendances = Attendance::where('class_id', $selectedClass)
                    ->where('date', $date)
                    ->get()
                    ->keyBy('student_id');

                $students->each(function ($student) use ($attendances) {
                    $student->attendance_today = $attendances->get($student->id);
                });
            }
        }

        return view('admin.attendance.index', compact('classes', 'selectedClass', 'date', 'students'));
    }

    /**
     * Grid presensi untuk kelas tertentu (route-model-binding /attendance/class/{class}/{date?}).
     * Reuse index() dengan kelas yang sudah dipilih agar konsisten dengan UI grid.
     */
    public function classGrid(Request $request, SchoolClass $class, ?string $date = null): View
    {
        $request->merge(['class_id' => $class->id]);
        if ($date) {
            $request->merge(['date' => $date]);
        }

        return $this->index($request);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        // 1. Check if user has permission to mark or edit attendance
        if (! $user->can('mark_attendance') && ! $user->can('edit_attendance')) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki akses untuk menyimpan presensi.',
            ], 403);
        }

        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:H,A,I,S,T',
        ]);

        $classId = $request->input('class_id');

        // 2. Restrict teachers (guru) to their assigned classes
        if ($user->hasRole('guru')) {
            $class = SchoolClass::find($classId);
            if (! $class || $class->teacher_id !== $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses untuk menyimpan presensi kelas ini.',
                ], 403);
            }
        }

        // Normalisasi ke Y-m-d agar updateOrCreate cocok dengan baris yang sudah ada.
        // (kolom `date` di-cast date → tersimpan 00:00:00; tanpa normalisasi,
        //  pembandingan string gagal cocok → duplikat & melanggar unique(student,date)).
        $date = Carbon::parse($request->input('date'))->toDateString();
        $records = $request->input('records');
        $userId = $request->user()->id;
        $tenant = app(\App\Support\Tenancy::class)->tenant();

        $saved = 0;
        foreach ($records as $record) {
            $attendance = Attendance::updateOrCreate(
                [
                    'student_id' => $record['student_id'],
                    'date' => $date,
                ],
                [
                    'tenant_id' => $tenant->id,
                    'class_id' => $classId,
                    'status' => $record['status'],
                    'arrival_time' => $record['status'] === 'H' ? now()->format('H:i') : null,
                    'notes' => $record['notes'] ?? null,
                    'marked_by' => $userId,
                ]
            );
            $saved++;

            // Dispatch WA notification for non-Hadir if enabled, or Hadir if configured
            $sendHadir = $tenant->settings['wa_notify_hadir'] ?? false;
            if ($record['status'] !== 'H' || $sendHadir) {
                $student = Student::find($record['student_id']);
                if ($student) {
                    foreach ($student->guardians as $guardian) {
                        SendWaNotification::dispatch(
                            $tenant->id,
                            $guardian->phone,
                            'attendance',
                            [
                                'student_name' => $student->name,
                                'status' => Attendance::statusLabel($record['status']),
                                'date' => Carbon::parse($date)->format('d F Y'),
                                'class' => SchoolClass::find($classId)->name ?? '',
                            ]
                        )->onQueue('wa');
                    }
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Presensi {$saved} siswa berhasil disimpan. Notifikasi WA diantrikan.",
        ]);
    }

    public function rekap(Request $request): View
    {
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $class = SchoolClass::with('students')->find($request->input('class_id'));
        if (! $class) {
            abort(404);
        }

        $user = $request->user();
        $isTeacherOfClass = ($user->hasRole('guru') && $class->teacher_id === $user->id);

        if (! $isTeacherOfClass && ! $user->can('view_attendance_rekap')) {
            abort(403, 'Anda tidak memiliki akses ke rekap presensi kelas ini.');
        }

        $month = $request->input('month');
        $students = $class->students;

        // Portable date-range filter (kompatibel SQLite & MySQL) — hindari DATE_FORMAT MySQL-only.
        $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
        $end = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();

        $attendances = Attendance::where('class_id', $class->id)
            ->whereBetween('date', [$start, $end])
            ->get()
            ->groupBy('student_id');

        $students->each(function ($student) use ($attendances) {
            $student->monthly = $attendances->get($student->id, collect())->keyBy(fn ($a) => $a->date->format('Y-m-d'));
        });

        return view('admin.attendance.rekap', compact('class', 'month', 'students'));
    }

    public function exportRecap(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:school_classes,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $class = SchoolClass::find($request->input('class_id'));
        if (! $class) {
            abort(404);
        }

        $user = $request->user();
        $isTeacherOfClass = ($user->hasRole('guru') && $class->teacher_id === $user->id);

        if (! $isTeacherOfClass && ! $user->can('view_attendance_rekap')) {
            abort(403, 'Anda tidak memiliki akses ke ekspor rekap presensi kelas ini.');
        }

        $month = $request->input('month');

        $fileName = 'rekap_presensi_' . str_replace(' ', '_', strtolower($class->name)) . '_' . $month . '.xlsx';

        return Excel::download(new AttendanceRecapExport($class, $month), $fileName);
    }
}
