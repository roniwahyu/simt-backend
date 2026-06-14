@extends('layouts.app')
@section('title', 'Rekap Presensi')
@section('content')
@php
    $start = \Carbon\Carbon::createFromFormat('Y-m', $month)->startOfMonth();
    $daysInMonth = $start->daysInMonth;
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-bold">Rekap Presensi Bulanan</h1>
        <span class="text-sm text-gray-500">{{ $class->name ?? '-' }} — {{ $start->translatedFormat('F Y') }}</span>
    </div>

    <form method="GET" class="bg-white rounded-lg shadow border border-gray-100 p-4 flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs text-gray-500 mb-1">Kelas ID</label>
            <input type="number" name="class_id" value="{{ $class->id }}" class="w-32 rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-xs text-gray-500 mb-1">Bulan</label>
            <input type="month" name="month" value="{{ $month }}" class="rounded-md border border-gray-300 px-3 py-2 text-sm">
        </div>
        <button class="px-4 py-2 rounded-lg bg-slate-800 hover:bg-slate-900 text-white text-sm">Tampilkan</button>
        <a href="{{ route('attendance.rekap.export', ['class_id' => $class->id, 'month' => $month]) }}" class="px-4 py-2 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
            Export Excel
        </a>
    </form>

    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-x-auto">
        <table class="min-w-full text-xs">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 text-left sticky left-0 bg-gray-50">Nama</th>
                    @for($d = 1; $d <= $daysInMonth; $d++)
                        <th class="px-1 py-2 text-center w-7">{{ $d }}</th>
                    @endfor
                    <th class="px-2 py-2 text-center">H</th>
                    <th class="px-2 py-2 text-center">A</th>
                    <th class="px-2 py-2 text-center">I</th>
                    <th class="px-2 py-2 text-center">S</th>
                    <th class="px-2 py-2 text-center">T</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($students as $s)
                    @php $counts = ['H'=>0,'A'=>0,'I'=>0,'S'=>0,'T'=>0]; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2 font-medium whitespace-nowrap sticky left-0 bg-white">{{ $s->name }}</td>
                        @for($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                $dateKey = $start->copy()->day($d)->format('Y-m-d');
                                $rec = $s->monthly[$dateKey] ?? null;
                                $st = $rec->status ?? null;
                                if ($st) $counts[$st]++;
                                $color = match($st) {
                                    'H' => 'text-emerald-600', 'A' => 'text-red-600',
                                    'I','S' => 'text-amber-600', 'T' => 'text-blue-600',
                                    default => 'text-gray-300',
                                };
                            @endphp
                            <td class="px-1 py-2 text-center {{ $color }}">{{ $st ?? '·' }}</td>
                        @endfor
                        <td class="px-2 py-2 text-center font-semibold text-emerald-600">{{ $counts['H'] }}</td>
                        <td class="px-2 py-2 text-center font-semibold text-red-600">{{ $counts['A'] }}</td>
                        <td class="px-2 py-2 text-center font-semibold text-amber-600">{{ $counts['I'] }}</td>
                        <td class="px-2 py-2 text-center font-semibold text-amber-600">{{ $counts['S'] }}</td>
                        <td class="px-2 py-2 text-center font-semibold text-blue-600">{{ $counts['T'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="{{ $daysInMonth + 6 }}" class="px-4 py-8 text-center text-gray-400">Tidak ada data.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <p class="text-xs text-gray-400">Keterangan: H=Hadir, A=Alpa, I=Izin, S=Sakit, T=Terlambat, ·=belum ada data.</p>
</div>
@endsection
