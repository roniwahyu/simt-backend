@extends('layouts.app')
@section('title', 'Edit Tenant')
@section('content')
<div class="max-w-xl mx-auto space-y-4">
    <h1 class="text-2xl font-bold">Edit Tenant — {{ $tenant->name }}</h1>
    <div class="bg-white rounded-lg shadow border border-gray-100 p-6 space-y-6">
        <form action="{{ route('super.tenant.update', $tenant) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nama Madrasah</label>
                <input type="text" name="name" value="{{ $tenant->name }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border rounded px-3 py-2">
                    @foreach(['prospect','contracted','active','grace_read','suspended','terminated'] as $s)
                    <option value="{{ $s }}" {{ $tenant->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Modul Aktif</label>
                <div class="grid grid-cols-2 gap-2">
                    @foreach($allModules as $mod)
                    @php $isActive = $tenant->hasModule($mod); @endphp
                    <label class="flex items-center gap-2 border rounded px-3 py-2 cursor-pointer hover:bg-gray-50">
                        <input type="checkbox" name="modules[{{ $mod }}]" value="1" {{ $isActive ? 'checked' : '' }}>
                        <span class="text-sm">{{ $mod }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            <div class="flex justify-end gap-2">
                <a href="{{ route('super.dashboard') }}" class="px-4 py-2 rounded border text-sm">Batal</a>
                <button type="submit" class="px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Simpan</button>
            </div>
        </form>
    </div>
</div>
@endsection
