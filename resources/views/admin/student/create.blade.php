@extends('layouts.app')
@section('title', 'Tambah Siswa')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-2xl font-bold">Tambah Siswa</h1>

    @if($errors->any())
        <div class="rounded-md bg-red-50 text-red-700 px-4 py-3 border border-red-200 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('students.store') }}" method="POST" class="bg-white rounded-lg shadow border border-gray-100 p-5 space-y-4">
        @csrf
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input name="name" value="{{ old('name') }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Jenis Kelamin</label>
                <select name="gender" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">- Pilih -</option>
                    <option value="L" @selected(old('gender')==='L')>Laki-laki</option>
                    <option value="P" @selected(old('gender')==='P')>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">NIS</label>
                <input name="nis" value="{{ old('nis') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">NISN</label>
                <input name="nisn" value="{{ old('nisn') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tempat Lahir</label>
                <input name="birth_place" value="{{ old('birth_place') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Lahir</label>
                <input type="date" name="birth_date" value="{{ old('birth_date') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Alamat</label>
                <textarea name="address" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('address') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Kelas</label>
                <select name="class_id" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">- Tidak ada -</option>
                    @foreach($classes as $class)
                        <option value="{{ $class->id }}" @selected(old('class_id')==$class->id)>{{ $class->name }} ({{ $class->schoolYear->name ?? '-' }})</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="border-t pt-4">
            <p class="text-sm font-semibold text-gray-600 mb-2">Wali (opsional — akun WA dibuat otomatis)</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1">Nama Wali</label>
                    <input name="guardian_name" value="{{ old('guardian_name') }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium mb-1">No. WA Wali</label>
                    <input name="guardian_phone" value="{{ old('guardian_phone') }}" placeholder="08xx / 628xx" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                </div>
            </div>
        </div>

        <div class="flex gap-2 pt-2">
            <button class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Simpan</button>
            <a href="{{ route('students.index') }}" class="px-5 py-2 rounded-lg border text-sm text-gray-600">Batal</a>
        </div>
    </form>
</div>
@endsection
