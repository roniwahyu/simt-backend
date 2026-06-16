<?php

namespace Modules\Student\Http\Controllers;

use Illuminate\Routing\Controller;
use App\Models\SchoolClass;
use App\Models\SchoolYear;
use App\Models\Student;
use App\Services\StudentImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $query = Student::query()->with('classes.schoolYear');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $hashedSearch = hash_hmac('sha256', $search, config('app.key'));
            $query->where(function ($q) use ($search, $hashedSearch) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('nis', 'like', '%' . $search . '%')
                  ->orWhere('nisn_bindex', $hashedSearch);
            });
        }

        if ($request->filled('class_id')) {
            $query->whereHas('classes', fn ($q) => $q->where('class_id', $request->input('class_id')));
        }

        $students = $query->paginate(50)->withQueryString();
        $classes = SchoolClass::with('schoolYear')->get();

        return view('student::index', compact('students', 'classes'));
    }

    public function create(): View
    {
        $classes = SchoolClass::with('schoolYear')->get();
        return view('student::create', compact('classes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $tenantId = app(\App\Support\Tenancy::class)->tenantId();
        if ($request->filled('nisn')) {
            $request->merge([
                'nisn_bindex' => hash_hmac('sha256', $request->input('nisn'), config('app.key'))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'nis' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('students', 'nis')->where('tenant_id', $tenantId)],
            'nisn' => ['nullable', 'string', 'max:50'],
            'nisn_bindex' => ['nullable', \Illuminate\Validation\Rule::unique('students', 'nisn_bindex')->where('tenant_id', $tenantId)],
            'gender' => 'nullable|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'address' => 'nullable|string',
            'class_id' => 'nullable|exists:school_classes,id',
            'guardian_phone' => 'nullable|string|max:20',
            'guardian_name' => 'nullable|string|max:255',
        ], [
            'nisn_bindex.unique' => 'NISN sudah terdaftar.',
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
        return view('student::edit', compact('student', 'classes'));
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $tenantId = app(\App\Support\Tenancy::class)->tenantId();
        if ($request->filled('nisn')) {
            $request->merge([
                'nisn_bindex' => hash_hmac('sha256', $request->input('nisn'), config('app.key'))
            ]);
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'nis' => ['nullable', 'string', 'max:50', \Illuminate\Validation\Rule::unique('students', 'nis')->where('tenant_id', $tenantId)->ignore($student->id)],
            'nisn' => ['nullable', 'string', 'max:50'],
            'nisn_bindex' => ['nullable', \Illuminate\Validation\Rule::unique('students', 'nisn_bindex')->where('tenant_id', $tenantId)->ignore($student->id)],
            'gender' => 'nullable|in:L,P',
            'birth_date' => 'nullable|date',
            'birth_place' => 'nullable|string|max:100',
            'address' => 'nullable|string',
        ], [
            'nisn_bindex.unique' => 'NISN sudah terdaftar.',
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

    // ==========================================
    // IMPORT EXCEL WIZARD (3 Step)
    // ==========================================

    /**
     * Step 1: Show upload form
     */
    public function importForm(): View
    {
        return view('student::import.form');
    }

    /**
     * Step 2: Upload → Validate → Preview
     */
    public function importUpload(Request $request, StudentImportService $importService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        $tenantId = app(\App\Support\Tenancy::class)->tenantId();
        $result = $importService->validate($tenantId, $request->file('file'));

        if (isset($result['summary']['error'])) {
            return back()->with('error', $result['summary']['error']);
        }

        return view('student::import.preview', $result);
    }

    /**
     * Step 3: Commit validated data
     */
    public function importCommit(Request $request, StudentImportService $importService): RedirectResponse
    {
        $request->validate(['token' => 'required|string']);

        $result = $importService->commit($request->input('token'));

        if (isset($result['error'])) {
            return redirect()->route('students.import.form')->with('error', $result['error']);
        }

        return redirect()->route('students.index')->with('success',
            "Import selesai: {$result['imported']} siswa ditambahkan, " .
            "{$result['skipped']} dilewati, {$result['waQueued']} notif WA diantrikan."
        );
    }
}
