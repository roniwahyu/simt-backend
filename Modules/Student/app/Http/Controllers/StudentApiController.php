<?php

namespace Modules\Student\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentApiController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        $query = Student::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $hashedSearch = hash_hmac('sha256', $search, config('app.key'));
            $query->where(function ($q) use ($search, $hashedSearch) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('nis', 'like', "%{$search}%")
                  ->orWhere('nisn_bindex', $hashedSearch);
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('classes', function ($q) use ($request) {
                $q->where('class_id', $request->input('class_id'));
            });
        }

        $students = $query->paginate(50)->withQueryString();

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }

    public function show(Student $student): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $student->load('guardians', 'classes'),
        ]);
    }

    public function bills(Request $request, Student $student): JsonResponse
    {
        $user = $request->user();
        $isGuardian = $user->guardianStudents()->where('student_id', $student->id)->exists();
        if (! $isGuardian) {
            return response()->json([
                'success' => false,
                'message' => 'Anda bukan wali dari siswa ini.',
            ], 403);
        }

        $bills = $student->bills()
            ->orderBy('period', 'desc')
            ->get(['id', 'period', 'component', 'amount', 'paid_amount', 'discount', 'status', 'due_date']);

        return response()->json([
            'success' => true,
            'data' => $bills->map(fn ($b) => [
                'id' => $b->id,
                'period' => $b->period,
                'component' => $b->component,
                'amount' => (float) $b->amount,
                'paid_amount' => (float) $b->paid_amount,
                'discount' => (float) $b->discount,
                'remaining' => $b->remaining(),
                'status' => $b->status,
                'due_date' => $b->due_date?->format('Y-m-d'),
            ]),
        ]);
    }
}
