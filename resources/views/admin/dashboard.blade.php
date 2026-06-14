@extends('layouts.app')
@section('title', 'Dashboard')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Dashboard</h1>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Total Siswa</div>
            <div class="text-2xl font-bold text-blue-700">{{ $stats['students_count'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Kelas</div>
            <div class="text-2xl font-bold text-blue-700">{{ $stats['classes_count'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Hadir Hari Ini</div>
            <div class="text-2xl font-bold text-emerald-600">{{ $stats['today_attendance'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Alpa Hari Ini</div>
            <div class="text-2xl font-bold text-red-600">{{ $stats['today_absent'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="px-4 py-3 border-b border-gray-100 font-semibold">Presensi Terbaru — {{ $today }}</div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Siswa</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentAttendances as $a)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $a->student->name ?? '-' }}</td>
                        <td class="px-4 py-2">
                            <span class="inline-flex px-2 py-1 rounded text-xs font-medium
                                {{ $a->status === 'H' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $a->status === 'A' ? 'bg-red-100 text-red-700' : '' }}
                                {{ in_array($a->status, ['I','S']) ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $a->status === 'T' ? 'bg-blue-100 text-blue-700' : '' }}
                            ">{{ \App\Models\Attendance::statusLabel($a->status) }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $a->updated_at->format('H:i') }}</td>
                    </tr>
                    @empty
                    <tr><td class="px-4 py-3 text-gray-500" colspan="3">Belum ada presensi hari ini.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
