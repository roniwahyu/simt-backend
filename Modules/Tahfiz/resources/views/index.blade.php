@extends('layouts.app')

@section('title', 'Tahfiz')

@section('content')
<div class="space-y-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Tahfiz</h1>
            <p class="text-slate-500 mt-1">Modul monitoring hafalan Quran, ziyadah, dan murajaah siswa.</p>
        </div>
    </div>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <p class="text-slate-600">Selamat datang di Modul Tahfiz. Gunakan portal ini untuk melihat dan merekam riwayat hafalan Quran.</p>
    </div>
</div>
@endsection
