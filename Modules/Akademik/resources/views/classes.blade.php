@extends('layouts.app')

@section('title', 'Kelola Rombongan Belajar')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb / Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-2 md:space-y-0">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <a href="{{ route('akademik.index') }}" class="hover:text-slate-800">Akademik</a>
                <span>&rarr;</span>
                <span class="text-slate-800 font-medium">Rombel</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight mt-1">Rombongan Belajar (Rombel)</h1>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- List of Classes (Left) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">Daftar Rombel</h3>
                <span class="text-xs text-slate-500 font-medium">Total: {{ $classes->total() }} Rombel</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="px-6 py-4">Tingkat</th>
                            <th class="px-6 py-4">Nama Rombel</th>
                            <th class="px-6 py-4">Tahun Pelajaran</th>
                            <th class="px-6 py-4">Wali Kelas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($classes as $class)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-semibold text-slate-900">Kelas {{ $class->grade }}</td>
                                <td class="px-6 py-4">{{ $class->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-50 text-blue-700">
                                        {{ $class->schoolYear->name ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-slate-600 font-medium">
                                    {{ $class->teacher->name ?? 'Belum ditentukan' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-slate-400">
                                    Belum ada data rombongan belajar.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($classes->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $classes->links() }}
                </div>
            @endif
        </div>

        <!-- Add Class Form (Right) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 mb-4">Tambah Rombel</h3>
            
            <form action="{{ route('akademik.classes.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Tingkat Kelas</label>
                    <select name="grade" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                        <option value="7">Kelas 7</option>
                        <option value="8">Kelas 8</option>
                        <option value="9">Kelas 9</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Nama Rombel</label>
                    <input type="text" name="name" required placeholder="Contoh: A, B, Unggulan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Tahun Pelajaran</label>
                    <select name="school_year_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                        @foreach ($schoolYears as $year)
                            <option value="{{ $year->id }}" {{ $year->is_active ? 'selected' : '' }}>
                                {{ $year->name }} {{ $year->is_active ? '(Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Wali Kelas</label>
                    <select name="teacher_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                        <option value="">Pilih Wali Kelas (Opsional)</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>

                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl transition duration-200 text-sm shadow-sm">
                    Simpan Rombel
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
