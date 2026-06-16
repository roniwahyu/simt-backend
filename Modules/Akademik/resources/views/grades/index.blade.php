@extends('layouts.app')

@section('title', 'Daftar Nilai Siswa')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <a href="{{ route('akademik.index') }}" class="hover:text-slate-800">Akademik</a>
                <span>&rarr;</span>
                <span class="text-slate-800 font-medium">Nilai Siswa</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight mt-1">Pengelolaan Nilai</h1>
        </div>
        <div class="flex space-x-3">
            <a href="{{ route('grades.create') }}" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-4 py-2.5 rounded-xl transition duration-200 text-sm shadow-sm flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Input Nilai Kelas
            </a>
            <a href="{{ route('grades.rapor') }}" class="bg-purple-600 hover:bg-purple-700 text-white font-semibold px-4 py-2.5 rounded-xl transition duration-200 text-sm shadow-sm flex items-center">
                E-Rapor
            </a>
        </div>
    </div>

    <!-- Filter Form Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <form action="{{ route('grades.index') }}" method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Rombel (Kelas)</label>
                <select name="class_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    <option value="">Semua Rombel</option>
                    @foreach ($classrooms as $cls)
                        <option value="{{ $cls->id }}" {{ request('class_id') == $cls->id ? 'selected' : '' }}>
                            Kelas {{ $cls->grade }}-{{ $cls->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Mata Pelajaran</label>
                <select name="subject_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    <option value="">Semua Mapel</option>
                    @foreach ($subjects as $sb)
                        <option value="{{ $sb->id }}" {{ request('subject_id') == $sb->id ? 'selected' : '' }}>
                            {{ $sb->name }} (Kelas {{ $sb->class->grade ?? '-' }}{{ $sb->class->name ?? '' }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Jenis Nilai</label>
                <select name="type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    <option value="">Semua Jenis</option>
                    @foreach (['UH1', 'UH2', 'UH3', 'UH4', 'UH5', 'UH6', 'UTS', 'UAS', 'TUGAS1', 'TUGAS2', 'TUGAS3', 'PRAKTIK', 'SIKAP'] as $t)
                        <option value="{{ $t }}" {{ request('type') == $t ? 'selected' : '' }}>
                            {{ $t }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button type="submit" class="w-full bg-slate-800 hover:bg-slate-900 text-white font-semibold py-2.5 rounded-xl transition duration-200 text-sm shadow-sm">
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    <!-- Table of Grades -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h3 class="font-bold text-slate-900">Daftar Nilai</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse text-sm">
                <thead>
                    <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                        <th class="px-6 py-4">Siswa</th>
                        <th class="px-6 py-4">Kelas</th>
                        <th class="px-6 py-4">Mata Pelajaran</th>
                        <th class="px-6 py-4">Jenis</th>
                        <th class="px-6 py-4 text-center">Skor</th>
                        <th class="px-6 py-4 text-center">Predikat</th>
                        <th class="px-6 py-4">Keterangan</th>
                        <th class="px-6 py-4 text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse ($grades as $grade)
                        <tr class="hover:bg-slate-50/50 transition duration-150">
                            <td class="px-6 py-4 font-semibold text-slate-900">
                                {{ $grade->student->name ?? '-' }}<br>
                                <span class="text-xs text-slate-400 font-normal">NIS: {{ $grade->student->nis ?? '-' }}</span>
                            </td>
                            <td class="px-6 py-4 text-slate-600 font-medium">
                                @if ($grade->student && $grade->student->classes->isNotEmpty())
                                    Kelas {{ $grade->student->classes->first()->grade }}-{{ $grade->student->classes->first()->name }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4">{{ $grade->subject->name ?? '-' }}</td>
                            <td class="px-6 py-4 font-semibold text-slate-500">{{ $grade->type }}</td>
                            <td class="px-6 py-4 text-center font-bold
                                @if($grade->score >= 75) text-emerald-600 @else text-rose-600 @endif">
                                {{ round($grade->score) }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold uppercase tracking-wider
                                    @if($grade->getGradePredicate() === 'A') bg-emerald-50 text-emerald-700 border border-emerald-100
                                    @elseif($grade->getGradePredicate() === 'B') bg-blue-50 text-blue-700 border border-blue-100
                                    @elseif($grade->getGradePredicate() === 'C') bg-amber-50 text-amber-700 border border-amber-100
                                    @else bg-rose-50 text-rose-700 border border-rose-100 @endif">
                                    {{ $grade->getGradePredicate() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-500 italic max-w-xs truncate" title="{{ $grade->description }}">
                                {{ $grade->description ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <a href="{{ route('grades.show', $grade->id) }}" class="text-blue-600 hover:text-blue-700 font-semibold">
                                    Edit
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-slate-400">
                                Tidak ada data nilai yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($grades->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $grades->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
