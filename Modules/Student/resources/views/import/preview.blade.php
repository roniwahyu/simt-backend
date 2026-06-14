@extends('layouts.app')
@section('title', 'Preview Import Siswa')

@section('content')
<div class="max-w-4xl mx-auto py-8">
    <h1 class="text-2xl font-bold mb-2">Preview Import Siswa</h1>
    <p class="text-gray-600 mb-6">Step 2 dari 3 — Review data sebelum commit</p>

    <!-- Summary -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-blue-600">{{ $summary['total'] }}</div>
            <div class="text-sm text-blue-800">Total Baris</div>
        </div>
        <div class="bg-green-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-green-600">{{ $summary['valid'] }}</div>
            <div class="text-sm text-green-800">Valid</div>
        </div>
        <div class="bg-red-50 rounded-lg p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $summary['errors'] }}</div>
            <div class="text-sm text-red-800">Error</div>
        </div>
    </div>

    <!-- Error rows -->
    @if(count($errorRows) > 0)
    <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-6">
        <h3 class="font-medium text-red-800 mb-2">⚠️ Baris dengan Error (akan dilewati):</h3>
        <div class="max-h-40 overflow-y-auto text-sm">
            @foreach($errorRows as $err)
                <div class="mb-1">
                    <strong>Baris {{ $err['line'] }}:</strong>
                    {{ implode(', ', $err['errors']) }}
                </div>
            @endforeach
        </div>
    </div>
    @endif

    <!-- Valid rows preview -->
    @if(count($validRows) > 0)
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <h3 class="font-medium mb-2">✅ Data yang akan di-import ({{ count($validRows) }} siswa):</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-2 py-1 text-left">NIS</th>
                        <th class="px-2 py-1 text-left">Nama</th>
                        <th class="px-2 py-1 text-left">L/P</th>
                        <th class="px-2 py-1 text-left">Kelas</th>
                        <th class="px-2 py-1 text-left">Wali</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($validRows as $row)
                    <tr class="border-t">
                        <td class="px-2 py-1">{{ $row['nis'] }}</td>
                        <td class="px-2 py-1">{{ $row['name'] }}</td>
                        <td class="px-2 py-1">{{ $row['gender'] }}</td>
                        <td class="px-2 py-1">{{ $row['class_id'] ? '✓' : '-' }}</td>
                        <td class="px-2 py-1">{{ $row['wali_phone'] ?: '-' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Step 3: Commit -->
    <form action="{{ route('students.import.commit') }}" method="POST" class="flex gap-3">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <button type="submit" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700">
            ✅ Commit Import ({{ count($validRows) }} siswa)
        </button>
        <a href="{{ route('students.import.form') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400">
            Batal
        </a>
    </form>
    @else
    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-6">
        Tidak ada data valid untuk di-import. Perbaiki file Excel dan upload ulang.
    </div>
    <a href="{{ route('students.import.form') }}" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 inline-block">
        ← Upload Ulang
    </a>
    @endif
</div>
@endsection
