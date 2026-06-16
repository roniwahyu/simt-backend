@extends('layouts.app')
@section('title', 'Tambah Tenant')
@section('content')
<div class="max-w-xl mx-auto space-y-4">
    <h1 class="text-2xl font-bold">Tambah Tenant Baru</h1>
    <div class="bg-white rounded-lg shadow border border-gray-100 p-6">
        <form action="{{ route('super.tenant.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Madrasah</label>
                <input type="text" name="name" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Subdomain</label>
                <div class="flex items-center gap-2">
                    <input type="text" name="domain" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <span class="text-sm text-gray-500 whitespace-nowrap">.simt.id</span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">No. HP</label>
                <input type="text" name="phone" class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Alamat</label>
                <textarea name="address" rows="3" class="w-full border rounded px-3 py-2"></textarea>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('super.dashboard') }}" class="px-4 py-2 rounded border text-sm">Batal</a>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
