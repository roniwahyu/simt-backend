@extends('layouts.app')

@section('title', 'Laporan Keuangan SPP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Laporan Rekapitulasi SPP</h1>
        <p class="text-slate-500 mt-1">Ringkasan bulanan total penerimaan kas, diskon, dan tunggakan per periode.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 overflow-x-auto">
        <a href="{{ route('finance.dashboard') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Dashboard</a>
        <a href="{{ route('finance.bills') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Data Tagihan</a>
        <a href="{{ route('finance.payments.history') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Riwayat Pembayaran</a>
        <a href="{{ route('finance.reports') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-blue-600 text-blue-600">Laporan Bulanan</a>
    </div>

    <!-- Monthly Summary Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100">
            <h3 class="font-bold text-slate-900 text-lg">Rekapitulasi Penerimaan per Periode</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold text-left">
                        <th class="px-6 py-4">Periode Bulan</th>
                        <th class="px-6 py-4 text-center">Jumlah Tagihan</th>
                        <th class="px-6 py-4 text-right">Total Nominal Tagihan</th>
                        <th class="px-6 py-4 text-right">Total Kas Diterima</th>
                        <th class="px-6 py-4 text-right">Total Diskon/Beasiswa</th>
                        <th class="px-6 py-4 text-right">Tunggakan/Piutang</th>
                        <th class="px-6 py-4 text-center">Ekspor Laporan</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-slate-700">
                    @forelse($monthlyRecap as $r)
                    @php
                        $tunggakan = max(0, (float)$r->total_tagihan - (float)$r->total_dibayar - (float)$r->total_diskon);
                    @endphp
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-900">{{ $r->period }}</td>
                        <td class="px-6 py-4 text-center font-medium">{{ $r->total_siswa }} siswa</td>
                        <td class="px-6 py-4 text-right font-mono font-medium text-slate-950">Rp {{ number_format($r->total_tagihan, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-emerald-600">Rp {{ number_format($r->total_dibayar, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-mono text-slate-500">Rp {{ number_format($r->total_diskon, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-rose-600">Rp {{ number_format($tunggakan, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center space-x-1.5 whitespace-nowrap">
                            <a href="{{ route('finance.reports.pdf', ['period' => $r->period]) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-200 hover:bg-red-50 text-red-600 text-xs font-semibold shadow-sm transition">
                                Cetak PDF
                            </a>
                            <a href="{{ route('finance.bills.export', ['period' => $r->period]) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg border border-emerald-200 hover:bg-emerald-50 text-emerald-600 text-xs font-semibold shadow-sm transition">
                                Excel
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">Belum ada data periode tagihan.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
