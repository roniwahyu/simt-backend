@extends('layouts.app')
@section('title', 'Edit Siswa')
@section('content')
<div class="max-w-2xl mx-auto space-y-4">
    <h1 class="text-2xl font-bold">Edit Siswa</h1>

    @if($errors->any())
        <div class="rounded-md bg-red-50 text-red-700 px-4 py-3 border border-red-200 text-sm">
            <ul class="list-disc pl-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <form action="{{ route('students.update', $student) }}" method="POST" class="bg-white rounded-lg shadow border border-gray-100 p-5 space-y-4">
        @csrf @method('PUT')
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                <input name="name" value="{{ old('name', $student->name) }}" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Jenis Kelamin</label>
                <select name="gender" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
                    <option value="">- Pilih -</option>
                    <option value="L" @selected(old('gender', $student->gender)==='L')>Laki-laki</option>
                    <option value="P" @selected(old('gender', $student->gender)==='P')>Perempuan</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">NIS</label>
                <input name="nis" value="{{ old('nis', $student->nis) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">NISN</label>
                <input name="nisn" value="{{ old('nisn', $student->nisn) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tempat Lahir</label>
                <input name="birth_place" value="{{ old('birth_place', $student->birth_place) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Tanggal Lahir</label>
                <input type="date" name="birth_date" value="{{ old('birth_date', optional($student->birth_date)->format('Y-m-d')) }}" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-sm font-medium mb-1">Alamat</label>
                <textarea name="address" rows="2" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm">{{ old('address', $student->address) }}</textarea>
            </div>
        </div>

        @if($student->guardians->count())
        <div class="border-t pt-4 text-sm text-gray-600">
            <p class="font-semibold mb-1">Wali Terkait</p>
            <ul class="list-disc pl-5">
                @foreach($student->guardians as $g)
                    <li>{{ $g->name }} — {{ $g->phone }} ({{ $g->pivot->relation }})</li>
                @endforeach
            </ul>
        </div>
        @endif

        <div class="flex gap-2 pt-2">
            <button class="px-5 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">Simpan Perubahan</button>
            <a href="{{ route('students.index') }}" class="px-5 py-2 rounded-lg border text-sm text-gray-600">Batal</a>
        </div>
    </form>
</div>
@endsection
