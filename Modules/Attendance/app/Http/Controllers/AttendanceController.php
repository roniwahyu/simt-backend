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

class AttendanceController extends Controller
{
    public function index(Request $request): View
    {
        $classes = SchoolClass::with('schoolYear')->get();
        $selectedClass = $request->input('class_id');
        $date = $request->input('date', now()->toDateString());

        $students = collect();
        if ($selectedClass) {
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

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date' => 'required|date',
            'records' => 'required|array',
            'records.*.student_id' => 'required|exists:students,id',
            'records.*.status' => 'required|in:H,A,I,S,T',
        ]);

        $classId = $request->input('class_id');
        $date = $request->input('date');
        $records = $request->input('records');
        $userId = $request->user()->id;
        $tenant = app('currentTenant');

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
            'class_id' => 'required|exists:classes,id',
            'month' => 'required|date_format:Y-m',
        ]);

        $class = SchoolClass::with('students')->find($request->input('class_id'));
        $month = $request->input('month');
        $students = $class->students;

        $attendances = Attendance::where('class_id', $class->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])
            ->get()
            ->groupBy('student_id');

        $students->each(function ($student) use ($attendances) {
            $student->monthly = $attendances->get($student->id, collect())->keyBy(fn ($a) => $a->date->format('Y-m-d'));
        });

        return view('admin.attendance.rekap', compact('class', 'month', 'students'));
    }
}
