@extends('layouts.app')
@section('title', 'Super Admin')
@section('content')
<div class="space-y-6">
    <h1 class="text-2xl font-bold">Super Admin Panel</h1>
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
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Nama</th>
                        <th class="px-4 py-2 text-left">Domain</th>
                        <th class="px-4 py-2 text-left">Status</th>
                        <th class="px-4 py-2 text-left">User</th>
                        <th class="px-4 py-2 text-left">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($tenants as $t)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $t->name }}</td>
                        <td class="px-4 py-2">{{ $t->domain }}.simt.id</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                {{ $t->status === 'active' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $t->status === 'suspended' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $t->status === 'prospect' ? 'bg-gray-100 text-gray-700' : '' }}
                            ">{{ $t->status }}</span>
                        </td>
                        <td class="px-4 py-2">{{ $t->users_count }}</td>
                        <td class="px-4 py-2">
                            <a href="{{ route('super.tenant.edit', $t) }}" class="text-blue-600 hover:underline">Edit</a>
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
