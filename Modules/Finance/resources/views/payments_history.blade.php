@extends('layouts.app')

@section('title', 'Riwayat Pembayaran SPP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div>
        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Riwayat Pembayaran</h1>
        <p class="text-slate-500 mt-1">Log kronologis seluruh pembayaran SPP dan iuran siswa.</p>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 overflow-x-auto">
        <a href="{{ route('finance.dashboard') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Dashboard</a>
        <a href="{{ route('finance.bills') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Data Tagihan</a>
        <a href="{{ route('finance.payments.history') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-blue-600 text-blue-600">Riwayat Pembayaran</a>
        <a href="{{ route('finance.reports') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Laporan Bulanan</a>
    </div>

    <!-- Filter Form -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <form method="GET" class="flex flex-col md:flex-row gap-4 items-end">
            <div class="flex-1 w-full">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Siswa</label>
                <select name="student_id" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none bg-white">
                    <option value="">Semua Siswa</option>
                    @foreach($students as $s)
                    <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="w-full md:w-48">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Metode</label>
                <select name="method" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none bg-white font-medium">
                    <option value="">Semua Metode</option>
                    <option value="cash" {{ request('method') == 'cash' ? 'selected' : '' }}>Tunai</option>
                    <option value="transfer" {{ request('method') == 'transfer' ? 'selected' : '' }}>Transfer Bank</option>
                </select>
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <button type="submit" class="flex-1 md:flex-none px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition">
                    Filter
                </button>
                <a href="{{ route('finance.payments.history') }}" class="inline-flex items-center px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition justify-center">
                    Reset
                </a>
            </div>
        </form>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold text-left">
                        <th class="px-6 py-4">Siswa</th>
                        <th class="px-6 py-4">No. Kwitansi</th>
                        <th class="px-6 py-4">Tanggal Bayar</th>
                        <th class="px-6 py-4">Komponen</th>
                        <th class="px-6 py-4">Metode</th>
                        <th class="px-6 py-4 text-right">Jumlah</th>
                        <th class="px-6 py-4">Operator</th>
                        <th class="px-6 py-4 text-center">Kwitansi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-slate-700">
                    @forelse($payments as $p)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-900">{{ $p->student->name ?? '-' }}</td>
                        <td class="px-6 py-4 font-mono text-xs text-slate-500">{{ $p->receipt_no }}</td>
                        <td class="px-6 py-4">{{ $p->payment_date?->translatedFormat('d F Y') ?? '-' }}</td>
                        <td class="px-6 py-4">{{ $p->bill->component ?? '-' }} ({{ $p->bill->period ?? '-' }})</td>
                        <td class="px-6 py-4">
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded {{ $p->method === 'transfer' ? 'bg-blue-50 text-blue-600' : 'bg-slate-100 text-slate-700' }}">
                                {{ strtoupper($p->method) }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-mono font-bold text-emerald-600">Rp {{ number_format($p->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-slate-500 text-xs">{{ $p->recorder->name ?? '-' }}</td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('finance.receipt', $p) }}" target="_blank" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition" title="Cetak Kwitansi PDF">
                                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Belum ada riwayat pembayaran yang tercatat.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-100">{{ $payments->links() }}</div>
    </div>
</div>
@endsection
