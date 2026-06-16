@extends('layouts.app')

@section('title', 'Input Nilai Rombel')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <a href="{{ route('akademik.index') }}" class="hover:text-slate-800">Akademik</a>
                <span>&rarr;</span>
                <a href="{{ route('grades.index') }}" class="hover:text-slate-800">Nilai</a>
                <span>&rarr;</span>
                <span class="text-slate-800 font-medium">Input Massal</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight mt-1">Input Nilai Massal</h1>
        </div>
    </div>

    <!-- Selector Form (Filters) -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <form action="{{ route('grades.create') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4" id="selector-form">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Rombel (Kelas)</label>
                <select name="class_id" onchange="document.getElementById('selector-form').submit()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    @foreach ($classrooms as $cls)
                        <option value="{{ $cls->id }}" {{ $selectedClassroom && $selectedClassroom->id == $cls->id ? 'selected' : '' }}>
                            Kelas {{ $cls->grade }}-{{ $cls->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Mata Pelajaran</label>
                <select name="subject_id" onchange="document.getElementById('selector-form').submit()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    @forelse ($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ $selectedSubject && $selectedSubject->id == $sb->id ? 'selected' : '' }}>
                            {{ $sb->name }}
                        </option>
                    @empty
                        <option value="">Belum ada mapel di kelas ini</option>
                    @endforelse
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Jenis Nilai</label>
                <select name="type" onchange="document.getElementById('selector-form').submit()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    @foreach (['UH1', 'UH2', 'UH3', 'UH4', 'UH5', 'UH6', 'UTS', 'UAS', 'TUGAS1', 'TUGAS2', 'TUGAS3', 'PRAKTIK', 'SIKAP'] as $t)
                        <option value="{{ $t }}" {{ $type == $t ? 'selected' : '' }}>
                            {{ $t }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-800 font-semibold py-2.5 rounded-xl transition duration-200 text-sm border border-slate-200">
                    Refresh Form
                </button>
            </div>
        </form>
    </div>

    <!-- Student Scoring Form -->
    @if ($selectedSubject && $students->isNotEmpty())
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/20">
                <h3 class="font-bold text-slate-900">
                    Mengisi Nilai: {{ $selectedSubject->name }} — {{ $type }} (Kelas {{ $selectedClassroom->grade }}-{{ $selectedClassroom->name }})
                </h3>
            </div>

            <form action="{{ route('grades.store') }}" method="POST">
                @csrf
                <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}">
                <input type="hidden" name="type" value="{{ $type }}">

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse text-sm">
                        <thead>
                            <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                                <th class="px-6 py-4 w-1/12 text-center">No</th>
                                <th class="px-6 py-4 w-2/12">NIS</th>
                                <th class="px-6 py-4 w-4/12">Nama Siswa</th>
                                <th class="px-6 py-4 w-2/12 text-center">Nilai (0-100)</th>
                                <th class="px-6 py-4 w-3/12">Catatan / Keterangan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700">
                            @foreach ($students as $index => $student)
                                <tr class="hover:bg-slate-50/50 transition duration-150">
                                    <td class="px-6 py-4 text-center font-medium text-slate-400">{{ $index + 1 }}</td>
                                    <td class="px-6 py-4 font-mono text-slate-600">{{ $student->nis }}</td>
                                    <td class="px-6 py-4 font-semibold text-slate-900">{{ $student->name }}</td>
                                    <td class="px-6 py-4 text-center">
                                        <input type="hidden" name="grades[{{ $index }}][student_id]" value="{{ $student->id }}">
                                        <input type="number" name="grades[{{ $index }}][score]" 
                                            value="{{ old("grades.{$index}.score", $student->existingGrade->score ?? '') }}" 
                                            min="0" max="100" required step="any"
                                            class="w-24 text-center bg-slate-50 border border-slate-200 rounded-lg px-2 py-1.5 text-slate-800 font-bold focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                                    </td>
                                    <td class="px-6 py-4">
                                        <input type="text" name="grades[{{ $index }}][description]" 
                                            value="{{ old("grades.{$index}.description", $student->existingGrade->description ?? '') }}" 
                                            placeholder="Catatan kemajuan..."
                                            class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-slate-700 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-6 py-5 border-t border-slate-100 bg-slate-50/30 flex justify-end">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-2.5 rounded-xl transition duration-200 text-sm shadow-md">
                        Simpan Semua Nilai
                    </button>
                </div>
            </form>
        </div>
    @else
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center text-slate-400">
            <svg class="w-12 h-12 text-slate-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            Belum ada mata pelajaran terdaftar pada kelas yang dipilih, atau kelas tidak memiliki siswa aktif.
        </div>
    @endif
</div>
@endsection
