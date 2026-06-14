<?php

namespace Modules\Attendance\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Attendance;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AttendanceApiController extends Controller
{
    public function index(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();

        // Ownership check: student must belong to the authenticated user (guardian)
        $isGuardian = $user->guardianStudents()->where('student_id', $student->id)->exists();
        if (! $isGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan wali dari siswa ini.',
            ], 403);
        }

        $request->validate([
            'month' => 'nullable|date_format:Y-m',
        ]);

        $month = $request->input('month', now()->format('Y-m'));

        $attendances = Attendance::where('student_id', $student->id)
            ->whereRaw("DATE_FORMAT(date, '%Y-%m') = ?", [$month])
            ->orderBy('date')
            ->get(['id', 'date', 'status', 'arrival_time', 'notes']);

        return response()->json([
            'success' => true,
            'data' => $attendances->map(fn ($a) => [
                'id' => $a->id,
                'date' => $a->date->format('Y-m-d'),
                'status' => $a->status,
                'status_label' => Attendance::statusLabel($a->status),
                'arrival_time' => $a->arrival_time?->format('H:i'),
                'notes' => $a->notes,
            ]),
        ]);
    }
}
