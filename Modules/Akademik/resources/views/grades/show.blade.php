@extends('layouts.app')

@section('title', 'Detail Nilai Siswa')

@section('content')
<div class="space-y-6 max-w-2xl mx-auto">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div class="flex items-center space-x-2 text-sm text-slate-500">
            <a href="{{ route('grades.index') }}" class="hover:text-slate-800">&larr; Kembali ke Daftar Nilai</a>
        </div>
    </div>

    <!-- Edit Card -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Edit Nilai Siswa</h1>
            <p class="text-slate-500 text-sm mt-1">Perbarui skor atau deskripsi catatan perkembangan siswa.</p>
        </div>

        <div class="border-t border-b border-slate-100 py-4 grid grid-cols-2 gap-4 text-sm">
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Nama Siswa</span>
                <span class="font-bold text-slate-800 mt-1 block">{{ $grade->student->name ?? '-' }}</span>
                <span class="text-xs text-slate-500 font-mono">NIS: {{ $grade->student->nis ?? '-' }}</span>
            </div>
            <div>
                <span class="block text-xs font-semibold text-slate-400 uppercase tracking-wider">Mata Pelajaran</span>
                <span class="font-bold text-slate-800 mt-1 block">{{ $grade->subject->name ?? '-' }}</span>
                <span class="text-xs text-slate-500 font-mono">Jenis: {{ $grade->type }}</span>
            </div>
        </div>

        <form action="{{ route('grades.update', $grade->id) }}" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            
            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Skor / Nilai (0-100)</label>
                <input type="number" name="score" value="{{ old('score', $grade->score) }}" min="0" max="100" required step="any"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 font-bold focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Keterangan / Deskripsi Rapor</label>
                <textarea name="description" placeholder="Deskripsi kemajuan akademik..." rows="4"
                    class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-700 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">{{ old('description', $grade->description) }}</textarea>
            </div>

            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl transition duration-200 text-sm shadow-md">
                Perbarui Nilai
            </button>
        </form>
    </div>
</div>
@endsection
