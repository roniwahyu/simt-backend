@extends('layouts.app')
@section('title', 'Super Admin')
@section('content')
<div class="space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Super Admin Panel</h1>
        <div class="flex items-center space-x-2">
            <a href="{{ route('super.audit-logs') }}" class="px-4 py-2 bg-white hover:bg-gray-50 text-gray-700 text-sm font-medium rounded border border-gray-200 transition">
                Audit Logs
            </a>
            <a href="{{ route('super.failed-jobs') }}" class="px-4 py-2 bg-red-50 hover:bg-red-100 text-red-700 text-sm font-medium rounded border border-red-200 transition">
                Failed Queue Jobs
            </a>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Total Tenant</div>
            <div class="text-2xl font-bold text-blue-700">{{ $stats['total_tenants'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Tenant Aktif</div>
            <div class="text-2xl font-bold text-emerald-600">{{ $stats['active_tenants'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Total User</div>
            <div class="text-2xl font-bold text-blue-700">{{ $stats['total_users'] }}</div>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border border-gray-100">
            <div class="text-sm text-gray-500">Suspended</div>
            <div class="text-2xl font-bold text-red-600">{{ $stats['suspended'] }}</div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <span class="font-semibold">Daftar Tenant</span>
            <a href="{{ route('super.tenant.create') }}" class="inline-flex items-center px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">+ Tambah Tenant</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50 text-gray-500 uppercase font-medium text-xs">
                    <tr>
                        <th class="px-4 py-3 text-left">Nama</th>
                        <th class="px-4 py-3 text-left">Domain</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-center">User</th>
                        <th class="px-4 py-3 text-center">Siswa</th>
                        <th class="px-4 py-3 text-center">Rombel</th>
                        <th class="px-4 py-3 text-center">Modul Aktif</th>
                        <th class="px-4 py-3 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($tenants as $t)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-800">{{ $t->name }}</td>
                        <td class="px-4 py-3 text-gray-500 font-mono text-xs">{{ $t->domain }}.simt.id</td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded text-xs font-semibold
                                {{ $t->status === 'active' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $t->status === 'suspended' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $t->status === 'contracted' ? 'bg-blue-100 text-blue-700' : '' }}
                                {{ $t->status === 'prospect' ? 'bg-gray-100 text-gray-700' : '' }}
                            ">{{ strtoupper($t->status) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center font-semibold text-blue-700">{{ $t->users_count }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $t->students_count }}</td>
                        <td class="px-4 py-3 text-center font-semibold text-gray-700">{{ $t->classes_count }}</td>
                        <td class="px-4 py-3 text-center">
                            <span class="px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded text-xs font-bold">{{ $t->modules_count }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <a href="{{ route('super.tenant.edit', $t) }}" class="text-blue-600 hover:text-blue-800 font-medium">Edit / Kelola</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $tenants->links() }}</div>
    </div>
</div>
@endsection
