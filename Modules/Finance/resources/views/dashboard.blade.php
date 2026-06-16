@extends('layouts.app')

@section('title', 'Dashboard Keuangan')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Modul Keuangan</h1>
        <p class="text-slate-500 mt-1">Pemantauan kas masuk, rekap tagihan SPP, pencatatan transaksi, dan notifikasi.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 overflow-x-auto">
        <a href="{{ route('finance.dashboard') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-blue-600 text-blue-600">Dashboard</a>
        <a href="{{ route('finance.bills') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Data Tagihan</a>
        <a href="{{ route('finance.payments.history') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Riwayat Pembayaran</a>
        <a href="{{ route('finance.reports') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Laporan Bulanan</a>
    </div>

    <!-- Metrics Grid -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <!-- Metric 1: Total Collected -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center space-x-4">
            <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-20c5.303 0 9.6 4.297 9.6 9.6 0 5.303-4.297 9.6-9.6 9.6-5.303 0-9.6-4.297-9.6-9.6 0-5.303 4.297-9.6 9.6-9.6z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Kas Masuk</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Metric 2: Total Tunggakan -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center space-x-4">
            <div class="p-3 bg-rose-50 rounded-xl text-rose-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Sisa Tunggakan</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</h3>
            </div>
        </div>

        <!-- Metric 3: Rasio Lunas -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center space-x-4">
            <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Rasio Efektivitas</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $rasioLunas }}%</h3>
            </div>
        </div>

        <!-- Metric 4: Bulan Ini -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex items-center space-x-4">
            <div class="p-3 bg-amber-50 rounded-xl text-amber-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Bulan Ini ({{ $currentMonth }})</p>
                <h3 class="text-2xl font-bold text-slate-900 mt-0.5">Rp {{ number_format($dibayarBulanIni, 0, ',', '.') }}</h3>
            </div>
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Recent Payments Log -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-50 pb-3">
                <h3 class="font-bold text-slate-900 text-lg">Pencatatan Pembayaran Terbaru</h3>
                <a href="{{ route('finance.payments.history') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-700">Lihat Semua →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="text-slate-400 font-semibold text-left border-b border-slate-50">
                            <th class="py-2.5">Siswa</th>
                            <th class="py-2.5">Kwitansi</th>
                            <th class="py-2.5">Metode</th>
                            <th class="py-2.5 text-right">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50 text-slate-700">
                        @forelse($recentPayments as $p)
                        <tr class="hover:bg-slate-50/30 transition">
                            <td class="py-3 font-semibold text-slate-900">{{ $p->student->name ?? '-' }}</td>
                            <td class="py-3 font-mono text-xs text-slate-500">{{ $p->receipt_no }}</td>
                            <td class="py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded {{ $p->method === 'transfer' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-700' }}">
                                    {{ strtoupper($p->method) }}
                                </span>
                            </td>
                            <td class="py-3 text-right font-mono font-bold text-emerald-600">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">Belum ada transaksi pembayaran.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Quick Info Panel -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-50 pb-3">
                <h3 class="font-bold text-slate-900 text-lg">Informasi Tambahan</h3>
            </div>
            <div class="space-y-3.5 text-sm">
                <div class="flex justify-between items-center py-2 border-b border-slate-50">
                    <span class="text-slate-500">Mata Uang</span>
                    <span class="font-semibold text-slate-900">Rupiah (IDR)</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-50">
                    <span class="text-slate-500">Auto Remind SMS/WA</span>
                    <span class="font-semibold text-emerald-600">Aktif (Queue)</span>
                </div>
                <div class="flex justify-between items-center py-2 border-b border-slate-50">
                    <span class="text-slate-500">Kapasitas Backup Database</span>
                    <span class="font-semibold text-blue-600">Terjadwal Harian</span>
                </div>
                <div class="p-3 bg-blue-50/50 text-blue-800 rounded-xl text-xs flex items-start space-x-2">
                    <svg class="w-4 h-4 text-blue-600 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Segala transaksi keuangan di-audit otomatis dalam log sistem demi keamanan data tenant.</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
