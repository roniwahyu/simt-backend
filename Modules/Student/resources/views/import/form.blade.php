@extends('layouts.app')
@section('title', 'Import Siswa')

@section('content')
<div class="max-w-2xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-2">Import Siswa dari Excel</h1>
    <p class="text-gray-600 mb-6">Step 1 dari 3 — Upload file Excel</p>

    @if(session('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            {{ session('error') }}
        </div>
    @endif

    <form action="{{ route('students.import.upload') }}" method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow p-6">
        @csrf
        <div class="mb-4">
            <label class="block text-sm font-medium mb-2">Pilih file Excel (.xlsx, .xls, .csv)</label>
            <input type="file" name="file" accept=".xlsx,.xls,.csv" required
                   class="block w-full border border-gray-300 rounded-lg p-2">
        </div>

        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
            <h3 class="font-medium text-yellow-800 mb-2">Format Kolom Excel:</h3>
            <table class="text-xs text-yellow-700">
                <tr><th class="text-left pr-4">Kolom</th><th>Wajib</th><th>Contoh</th></tr>
                <tr><td class="pr-4">nis</td><td>✅</td><td>0001</td></tr>
                <tr><td class="pr-4">nisn</td><td>-</td><td>0012345678</td></tr>
                <tr><td class="pr-4">nama</td><td>✅</td><td>Ahmad Fauzi</td></tr>
                <tr><td class="pr-4">jenis_kelamin</td><td>-</td><td>L / P</td></tr>
                <tr><td class="pr-4">tanggal_lahir</td><td>-</td><td>2012-05-15</td></tr>
                <tr><td class="pr-4">tempat_lahir</td><td>-</td><td>Malang</td></tr>
                <tr><td class="pr-4">alamat</td><td>-</td><td>Jl. Siswa No.1</td></tr>
                <tr><td class="pr-4">kelas</td><td>-</td><td>7A</td></tr>
                <tr><td class="pr-4">wali_phone</td><td>-</td><td>081234567890</td></tr>
                <tr><td class="pr-4">wali_nama</td><td>-</td><td>H. Ahmad</td></tr>
                <tr><td class="pr-4">hubungan</td><td>-</td><td>ayah / ibu / wali</td></tr>
            </table>
        </div>

        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700">
            Upload & Validasi →
        </button>
    </form>
</div>
@endsection
