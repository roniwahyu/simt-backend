@extends('layouts.app')

@section('title', 'E-Rapor Digital Madrasah')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <a href="{{ route('akademik.index') }}" class="hover:text-slate-800">Akademik</a>
                <span>&rarr;</span>
                <span class="text-slate-800 font-medium">E-Rapor</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight mt-1">E-Rapor Digital Madrasah</h1>
        </div>
        @if ($student)
            <div>
                <a href="{{ route('grades.rapor', ['student_id' => $student->id, 'export' => 'pdf']) }}" target="_blank"
                    class="bg-rose-600 hover:bg-rose-700 text-white font-semibold px-4 py-2.5 rounded-xl transition duration-200 text-sm shadow-sm flex items-center">
                    <svg class="w-4.5 h-4.5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Cetak Rapor PDF
                </a>
            </div>
        @endif
    </div>

    <!-- Student Selector -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <form action="{{ route('grades.rapor') }}" method="GET" class="flex flex-col md:flex-row md:items-center space-y-4 md:space-y-0 md:space-x-4">
            <div class="space-y-1.5 flex-1">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Pilih Siswa</label>
                <select name="student_id" onchange="this.form.submit()" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                    @foreach ($students as $st)
                        <option value="{{ $st->id }}" {{ $student && $student->id == $st->id ? 'selected' : '' }}>
                            {{ $st->name }} (NIS: {{ $st->nis }})
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    @if ($student)
        <!-- Student Profile Card -->
        <div class="bg-slate-900 text-white rounded-2xl p-6 shadow-md grid grid-cols-1 md:grid-cols-4 gap-6">
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Lengkap</span>
                <span class="font-bold text-lg text-white mt-1 block">{{ $student->name }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">NIS / NISN</span>
                <span class="font-semibold text-slate-200 mt-1 block">{{ $student->nis }} / {{ $student->nisn ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Rombel (Kelas)</span>
                <span class="font-semibold text-slate-200 mt-1 block">
                    Kelas {{ $student->currentClass()->grade ?? '-' }}-{{ $student->currentClass()->name ?? '-' }}
                </span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Tahun Pelajaran</span>
                <span class="font-semibold text-slate-200 mt-1 block">
                    {{ $student->currentClass()->schoolYear->name ?? '-' }}
                </span>
            </div>
        </div>

        <!-- Rapor Table -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">Nilai Hasil Belajar</h3>
                <span class="text-xs text-slate-500 font-semibold">Kriteria Kelulusan Minimal (KKM): <strong class="text-slate-800">75</strong></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="px-6 py-4 w-1/12 text-center">No</th>
                            <th class="px-6 py-4 w-4/12">Mata Pelajaran</th>
                            <th class="px-6 py-4 text-center">Rata-Rata UH</th>
                            <th class="px-6 py-4 text-center">Rata-Rata Tugas</th>
                            <th class="px-6 py-4 text-center">UTS</th>
                            <th class="px-6 py-4 text-center">UAS</th>
                            <th class="px-6 py-4 text-center bg-blue-50/50 text-blue-900">Pengetahuan</th>
                            <th class="px-6 py-4 text-center bg-emerald-50/50 text-emerald-900">Keterampilan</th>
                            <th class="px-6 py-4 text-center">Sikap</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($raporData as $index => $row)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 text-center font-medium text-slate-400">{{ $index + 1 }}</td>
                                <td class="px-6 py-4">
                                    <span class="font-bold text-slate-900 block">{{ $row['subject']->name }}</span>
                                    <span class="text-[10px] uppercase font-semibold text-slate-400 tracking-wider">
                                        {{ str_replace('_', ' ', $row['subject']->category) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">{{ $row['uh_average'] }}</td>
                                <td class="px-6 py-4 text-center">{{ $row['tugas_average'] }}</td>
                                <td class="px-6 py-4 text-center">{{ $row['uts'] }}</td>
                                <td class="px-6 py-4 text-center">{{ $row['uas'] }}</td>
                                <td class="px-6 py-4 text-center bg-blue-50/20 font-bold text-blue-700">
                                    {{ $row['pengetahuan'] }}
                                    <span class="text-xs text-blue-500 font-normal">({{ $row['predicate_pengetahuan'] }})</span>
                                </td>
                                <td class="px-6 py-4 text-center bg-emerald-50/20 font-bold text-emerald-700">
                                    {{ $row['keterampilan'] }}
                                    <span class="text-xs text-emerald-500 font-normal">({{ $row['predicate_keterampilan'] }})</span>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-slate-800">
                                    {{ $row['sikap'] ?: '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-10 text-center text-slate-400">
                                    Belum ada data nilai mata pelajaran untuk siswa ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Attendance Summary -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 max-w-md">
            <h3 class="font-bold text-slate-900 mb-4">Ketidakhadiran (Bulan Ini)</h3>
            <div class="grid grid-cols-4 gap-4 text-center">
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-100">
                    <span class="block text-xs font-semibold text-slate-400 uppercase">Hadir</span>
                    <span class="block text-xl font-bold text-slate-800 mt-1">{{ $attendanceSummary['hadir'] }}</span>
                </div>
                <div class="bg-amber-50/50 p-3 rounded-xl border border-amber-100">
                    <span class="block text-xs font-semibold text-amber-500 uppercase">Sakit</span>
                    <span class="block text-xl font-bold text-amber-700 mt-1">{{ $attendanceSummary['sakit'] }}</span>
                </div>
                <div class="bg-blue-50/50 p-3 rounded-xl border border-blue-100">
                    <span class="block text-xs font-semibold text-blue-500 uppercase">Izin</span>
                    <span class="block text-xl font-bold text-blue-700 mt-1">{{ $attendanceSummary['izin'] }}</span>
                </div>
                <div class="bg-rose-50/50 p-3 rounded-xl border border-rose-100">
                    <span class="block text-xs font-semibold text-rose-500 uppercase">Alpha</span>
                    <span class="block text-xl font-bold text-rose-700 mt-1">{{ $attendanceSummary['alpha'] }}</span>
                </div>
            </div>
        </div>
    @endif
</div>
@endsection
