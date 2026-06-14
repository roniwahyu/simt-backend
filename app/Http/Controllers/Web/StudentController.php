<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::query()->with('classes.schoolYear');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->input('search') . '%')
                ->orWhere('nis', 'like', '%' . $request->input('search') . '%')
                ->orWhere('nisn', 'like', '%' . $request->input('search') . '%');
        }

        if ($request->filled('class_id')) {
            $query->whereHas('classes', fn ($q) => $q->where('class_id', $request->input('class_id')));
        }

        $students = $query->paginate(50)->withQueryString();
        $classes = SchoolClass::with('schoolYear')->get();

        return view('admin.student.index', compact('students', 'classes'));
    }

    public function create(): View
    {
        $classes = SchoolClass::with('schoolYear')->get();
        return view('admin.student.create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:50',
            'nisn' => 'nullable|string|max:50',
            'gender' => 'nullable|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'class_id' => 'nullable|exists:classes,id',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
        ]);

        $student = Student::create($request->only([
            'name', 'nis', 'nisn', 'gender', 'birth_date', 'birth_place', 'address'
        ]));

        if ($request->filled('class_id')) {
            $class = SchoolClass::find($request->input('class_id'));
            if ($class) {
                $student->classes()->attach($class->id, ['school_year_id' => $class->school_year_id]);
            }
        }

        // If guardian phone provided, create/link user
        if ($request->filled('guardian_phone')) {
            $guardian = \App\Models\User::firstOrCreate(
                ['phone' => $request->input('guardian_phone')],
                [
                    'tenant_id' => $student->tenant_id,
                    'name' => $request->input('guardian_name', 'Wali ' . $student->name),
                    'password' => \Illuminate\Support\Facades\Hash::make(\Illuminate\Support\Str::random(8)),
                    'role_display' => 'wali',
                ]
            );
            $guardian->assignRole('wali');
            $student->guardians()->attach($guardian->id, ['relation' => 'wali']);
        }

        return redirect()->route('students.index')->with('success', 'Siswa berhasil ditambahkan.');
    }

    public function edit(Student $student): View
    {
        $classes = SchoolClass::with('schoolYear')->get();
        $student->load('guardians', 'classes');
        return view('admin.student.edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'nis' => 'nullable|string|max:50',
            'nisn' => 'nullable|string|max:50',
            'gender' => 'nullable|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ]);

        $student->update($request->only([
            'name', 'nis', 'nisn', 'gender', 'birth_date', 'birth_place', 'address'
        ]));

        return redirect()->route('students.index')->with('success', 'Siswa berhasil diperbarui.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        $student->delete();
        return redirect()->route('students.index')->with('success', 'Siswa berhasil dihapus.');
    }
}
