@extends('layouts.app')
@section('title', 'Audit Logs — Admin')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Log Aktivitas Pengguna</h1>
        <a href="{{ route('dashboard') }}" class="px-4 py-2 rounded border text-sm bg-white hover:bg-gray-50">Kembali ke Dashboard</a>
    </div>

    <!-- Filter Card -->
    <div class="bg-white rounded-lg shadow border border-gray-100 p-4">
        <form action="{{ route('audit-logs') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Aktor (User)</label>
                <select name="user_id" class="w-full border rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">Semua User</option>
                    @foreach($users as $u)
                        <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Event</label>
                <select name="event" class="w-full border rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">Semua Event</option>
                    <option value="created" {{ request('event') === 'created' ? 'selected' : '' }}>Created</option>
                    <option value="updated" {{ request('event') === 'updated' ? 'selected' : '' }}>Updated</option>
                    <option value="deleted" {{ request('event') === 'deleted' ? 'selected' : '' }}>Deleted</option>
                    <option value="login" {{ request('event') === 'login' ? 'selected' : '' }}>Login</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tipe Data</label>
                <select name="auditable_type" class="w-full border rounded px-2 py-1.5 text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">
                    <option value="">Semua Tipe</option>
                    <option value="Student" {{ request('auditable_type') === 'Student' ? 'selected' : '' }}>Student</option>
                    <option value="Bill" {{ request('auditable_type') === 'Bill' ? 'selected' : '' }}>Bill</option>
                    <option value="Payment" {{ request('auditable_type') === 'Payment' ? 'selected' : '' }}>Payment</option>
                    <option value="User" {{ request('auditable_type') === 'User' ? 'selected' : '' }}>User</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-500 mb-1">Tanggal</label>
                <div class="flex gap-2">
                    <input type="date" name="date" value="{{ request('date') }}" class="w-full border rounded px-2 py-1 text-sm">
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Filter</button>
                    @if(request()->anyFilled(['user_id', 'event', 'auditable_type', 'date']))
                        <a href="{{ route('audit-logs') }}" class="bg-gray-100 text-gray-700 px-3 py-1.5 rounded text-sm hover:bg-gray-250 flex items-center justify-center">Reset</a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    <!-- Logs Table -->
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase font-medium text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Waktu</th>
                        <th class="px-4 py-3 text-left">Aktor</th>
                        <th class="px-4 py-3 text-left">Event</th>
                        <th class="px-4 py-3 text-left">Objek</th>
                        <th class="px-4 py-3 text-left">Detail Perubahan</th>
                        <th class="px-4 py-3 text-left">IP / User Agent</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($logs as $log)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap text-gray-500">{{ $log->created_at ? \Carbon\Carbon::parse($log->created_at)->setTimezone('Asia/Jakarta')->format('Y-m-d H:i:s') : '-' }} WIB</td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="font-semibold">{{ $log->user->name ?? 'Guest/System' }}</span>
                            @if($log->user)
                                <div class="text-xs text-gray-400">{{ $log->user->role_display }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $log->event === 'created' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $log->event === 'updated' ? 'bg-amber-100 text-amber-700' : '' }}
                                {{ $log->event === 'deleted' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $log->event === 'login' ? 'bg-blue-100 text-blue-700' : '' }}
                            ">{{ strtoupper($log->event) }}</span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                            <span class="font-mono text-xs">{{ $log->model_name }}</span>
                            <span class="text-xs text-gray-400">#{{ $log->auditable_id }}</span>
                        </td>
                        <td class="px-4 py-3 max-w-xs overflow-hidden">
                            @if($log->event === 'updated')
                                <div class="text-xs space-y-1">
                                    @if($log->old_values)
                                        <div class="text-red-600 line-through truncate" title="{{ json_encode($log->old_values) }}">Sebelum: {{ json_encode($log->old_values) }}</div>
                                    @endif
                                    @if($log->new_values)
                                        <div class="text-emerald-700 font-semibold truncate" title="{{ json_encode($log->new_values) }}">Sesudah: {{ json_encode($log->new_values) }}</div>
                                    @endif
                                </div>
                            @elseif($log->event === 'created' && $log->new_values)
                                <div class="text-xs text-gray-500 truncate" title="{{ json_encode($log->new_values) }}">{{ json_encode($log->new_values) }}</div>
                            @elseif($log->event === 'deleted' && $log->old_values)
                                <div class="text-xs text-red-600 line-through truncate" title="{{ json_encode($log->old_values) }}">{{ json_encode($log->old_values) }}</div>
                            @else
                                <span class="text-gray-400">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-xs text-gray-400" title="{{ $log->user_agent }}">
                            <div>{{ $log->ip_address ?? 'N/A' }}</div>
                            <div class="truncate max-w-[150px]">{{ $log->user_agent }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-500">Tidak ada log aktivitas yang tercatat.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="px-4 py-3 border-t border-gray-150">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
@endsection
