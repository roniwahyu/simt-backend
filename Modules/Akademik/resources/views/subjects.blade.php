@extends('layouts.app')

@section('title', 'Kelola Mata Pelajaran')

@section('content')
<div class="space-y-6">
    <!-- Breadcrumb / Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-2 md:space-y-0">
        <div>
            <div class="flex items-center space-x-2 text-sm text-slate-500">
                <a href="{{ route('akademik.index') }}" class="hover:text-slate-800">Akademik</a>
                <span>&rarr;</span>
                <span class="text-slate-800 font-medium">Mata Pelajaran</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight mt-1">Mata Pelajaran (Mapel)</h1>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        <!-- List of Subjects (Left) -->
        <div class="lg:col-span-8 bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-slate-900">Daftar Mapel</h3>
                <span class="text-xs text-slate-500 font-medium">Total: {{ $subjects->total() }} Mapel</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse text-sm">
                    <thead>
                        <tr class="bg-slate-50/75 border-b border-slate-100 text-slate-600 font-semibold uppercase tracking-wider text-[11px]">
                            <th class="px-6 py-4">Kode</th>
                            <th class="px-6 py-4">Nama Mapel</th>
                            <th class="px-6 py-4">Kategori</th>
                            <th class="px-6 py-4">Rombel</th>
                            <th class="px-6 py-4">Jam/Minggu</th>
                            <th class="px-6 py-4">Guru Pengampu</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @forelse ($subjects as $subject)
                            <tr class="hover:bg-slate-50/50 transition duration-150">
                                <td class="px-6 py-4 font-mono font-semibold text-slate-900">{{ $subject->code ?? '-' }}</td>
                                <td class="px-6 py-4">{{ $subject->name }}</td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold uppercase tracking-wider
                                        @if($subject->category === 'UMUM') bg-slate-100 text-slate-700
                                        @elseif($subject->category === 'AGAMA_ISLAM') bg-emerald-50 text-emerald-700 border border-emerald-100
                                        @elseif($subject->category === 'MUATAN_LOKAL') bg-amber-50 text-amber-700
                                        @else bg-blue-50 text-blue-700 @endif">
                                        {{ str_replace('_', ' ', $subject->category) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-800">Kelas {{ $subject->class->grade ?? '-' }}-{{ $subject->class->name ?? '-' }}</span>
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-medium">
                                    {{ $subject->hours_per_week }} Jam
                                </td>
                                <td class="px-6 py-4 text-slate-600 font-medium">
                                    {{ $subject->teacher->name ?? 'Belum ditentukan' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-slate-400">
                                    Belum ada data mata pelajaran.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($subjects->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $subjects->links() }}
                </div>
            @endif
        </div>

        <!-- Add Subject Form (Right) -->
        <div class="lg:col-span-4 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <h3 class="font-bold text-slate-900 mb-4">Tambah Mapel</h3>
            
            <form action="{{ route('akademik.subjects.store') }}" method="POST" class="space-y-4">
                @csrf
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Nama Mata Pelajaran</label>
                    <input type="text" name="name" required placeholder="Contoh: Fiqih, IPA, Matematika" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Kode Mapel (Opsional)</label>
                    <input type="text" name="code" placeholder="Contoh: FIQ-7, IPA-8" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Kategori</label>
                    <select name="category" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                        <option value="UMUM">UMUM</option>
                        <option value="AGAMA_ISLAM">AGAMA ISLAM</option>
                        <option value="MUATAN_LOKAL">MUATAN LOKAL</option>
                        <option value="PENGEMBANGAN_DIRI">PENGEMBANGAN DIRI</option>
                        <option value="EKSTRAKURIKULER">EKSTRAKURIKULER</option>
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Rombongan Belajar (Rombel)</label>
                    <select name="school_class_id" required class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                        <option value="">Pilih Rombel</option>
                        @foreach ($classes as $cls)
                            <option value="{{ $cls->id }}">Kelas {{ $cls->grade }}-{{ $cls->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Guru Pengampu</label>
                    <select name="teacher_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                        <option value="">Pilih Guru Pengampu (Opsional)</option>
                        @foreach ($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider">Jam per Minggu</label>
                    <input type="number" name="hours_per_week" required value="2" min="1" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-slate-800 focus:outline-none focus:border-blue-500 transition duration-200 text-sm">
                </div>

                <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-700 text-white font-semibold py-2.5 rounded-xl transition duration-200 text-sm shadow-sm">
                    Simpan Mapel
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
