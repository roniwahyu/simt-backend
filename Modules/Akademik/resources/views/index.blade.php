@extends('layouts.app')

@section('title', 'Dashboard Akademik')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Akademik & Kurikulum</h1>
            <p class="text-slate-500 mt-1">Kelola rombongan belajar, mata pelajaran, dan nilai rapor siswa.</p>
        </div>
    </div>

    <!-- Quick Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Card 1: Rombel -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <span class="text-xs font-semibold text-blue-600 bg-blue-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Rombel</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold text-slate-900">{{ $classes->count() }}</h3>
                <p class="text-slate-500 text-sm mt-1">Rombongan Belajar Aktif</p>
            </div>
            <div class="mt-6 border-t border-slate-100 pt-4">
                <a href="{{ route('akademik.classes') }}" class="text-sm font-semibold text-blue-600 hover:text-blue-700 flex items-center">
                    Kelola Rombel
                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Card 2: Mapel -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 flex items-center justify-center text-emerald-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                    </svg>
                </div>
                <span class="text-xs font-semibold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full uppercase tracking-wider">Mapel</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold text-slate-900">{{ $subjects->count() }}</h3>
                <p class="text-slate-500 text-sm mt-1">Mata Pelajaran</p>
            </div>
            <div class="mt-6 border-t border-slate-100 pt-4">
                <a href="{{ route('akademik.subjects') }}" class="text-sm font-semibold text-emerald-600 hover:text-emerald-700 flex items-center">
                    Kelola Mapel
                    <svg class="w-4 h-4 ml-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
        </div>

        <!-- Card 3: E-Rapor -->
        <div class="bg-white p-6 rounded-2xl border border-slate-100 shadow-sm hover:shadow-md transition duration-300">
            <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <span class="text-xs font-semibold text-purple-600 bg-purple-50 px-2.5 py-1 rounded-full uppercase tracking-wider">E-Rapor</span>
            </div>
            <div class="mt-4">
                <h3 class="text-2xl font-bold text-slate-900">E-Rapor</h3>
                <p class="text-slate-500 text-sm mt-1">Evaluasi & Cetak Rapor Digital</p>
            </div>
            <div class="mt-6 border-t border-slate-100 pt-4 flex space-x-4">
                <a href="{{ route('grades.index') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-700 flex items-center">
                    Input Nilai
                </a>
                <span class="text-slate-300">|</span>
                <a href="{{ route('grades.rapor') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-700 flex items-center">
                    Cetak Rapor
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
