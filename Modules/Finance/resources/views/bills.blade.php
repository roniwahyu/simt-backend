@extends('layouts.app')

@section('title', 'Tagihan SPP')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Tagihan SPP & Iuran</h1>
            <p class="text-slate-500 mt-1">Kelola tagihan SPP bulanan, pembayaran manual siswa, dan pengingat notifikasi WhatsApp.</p>
        </div>
        <div class="flex items-center space-x-3">
            <button onclick="document.getElementById('create-single-modal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold shadow-sm transition">
                <svg class="w-4.5 h-4.5 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                </svg>
                Tambah Tagihan
            </button>
            <button onclick="document.getElementById('generate-modal').classList.remove('hidden')" class="inline-flex items-center px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold shadow-sm transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Generate Massal
            </button>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="flex border-b border-slate-200 overflow-x-auto">
        <a href="{{ route('finance.dashboard') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Dashboard</a>
        <a href="{{ route('finance.bills') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-blue-600 text-blue-600">Data Tagihan</a>
        <a href="{{ route('finance.payments.history') }}" class="py-3 px-5 font-semibold text-sm border-b-2 whitespace-nowrap border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300 border-b-2">Riwayat Pembayaran</a>
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
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Status</label>
                <select name="status" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none bg-white font-medium">
                    <option value="">Semua Status</option>
                    <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Parsial</option>
                    <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
                </select>
            </div>
            <div class="w-full md:w-48">
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Periode</label>
                <input type="month" name="period" value="{{ request('period') }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none text-slate-700 font-semibold bg-white">
            </div>
            <div class="flex space-x-2 w-full md:w-auto">
                <button type="submit" class="flex-1 md:flex-none px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white text-sm font-semibold shadow-sm transition">
                    Filter
                </button>
                <a href="{{ route('finance.bills.export', request()->only(['period', 'status', 'student_id'])) }}" class="inline-flex items-center px-4 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold shadow-sm transition justify-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Excel
                </a>
            </div>
        </form>
    </div>

    <!-- Bills Table -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100 text-slate-500 font-semibold text-left">
                        <th class="px-6 py-4">Nama Siswa</th>
                        <th class="px-6 py-4">Periode</th>
                        <th class="px-6 py-4">Komponen</th>
                        <th class="px-6 py-4 text-right">Tagihan</th>
                        <th class="px-6 py-4 text-right">Dibayar</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-slate-700">
                    @forelse($bills as $b)
                    <tr class="hover:bg-slate-50/50 transition">
                        <td class="px-6 py-4 font-semibold text-slate-900">{{ $b->student->name ?? '-' }}</td>
                        <td class="px-6 py-4 font-semibold text-slate-600">{{ $b->period }}</td>
                        <td class="px-6 py-4">{{ $b->component }}</td>
                        <td class="px-6 py-4 text-right font-mono font-medium text-slate-950">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-right font-mono font-medium text-emerald-600">Rp {{ number_format($b->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-6 py-4 text-center">
                            @if($b->status === 'paid')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 mr-1.5"></span>
                                    Lunas
                                </span>
                            @elseif($b->status === 'partial')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5"></span>
                                    Sebagian
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500 mr-1.5"></span>
                                    Belum Bayar
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right space-x-1.5 whitespace-nowrap">
                            @if($b->status !== 'paid')
                            <button onclick="openPayment({{ $b->id }}, {{ $b->remaining() }})" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-emerald-200 hover:bg-emerald-50 text-emerald-600 text-xs font-semibold shadow-sm transition">
                                Bayar
                            </button>
                            <button onclick="openEditBill({{ $b->id }}, '{{ $b->component }}', {{ (int)$b->amount }}, '{{ $b->due_date ? $b->due_date->toDateString() : '' }}')" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 text-slate-600 text-xs font-semibold shadow-sm transition">
                                Edit
                            </button>
                            <form action="{{ route('finance.reminders') }}" method="POST" class="inline">
                                @csrf
                                <input type="hidden" name="bill_ids[]" value="{{ $b->id }}">
                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-amber-200 hover:bg-amber-50 text-amber-600 text-xs font-semibold shadow-sm transition" title="Kirim Pengingat WhatsApp ke Orang Tua">
                                    Notif
                                </button>
                            </form>
                            @endif

                            @if($b->status === 'unpaid')
                            <form action="{{ route('finance.bills.destroy', $b->id) }}" method="POST" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus tagihan ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="inline-flex items-center px-2.5 py-1.5 rounded-lg border border-rose-200 hover:bg-rose-50 text-rose-600 text-xs font-semibold shadow-sm transition">
                                    Hapus
                                </button>
                            </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Belum ada data tagihan SPP.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-6 py-4 border-t border-slate-100">{{ $bills->links() }}</div>
    </div>
</div>

<!-- Modal Generate Massal -->
<div id="generate-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-100 max-w-md w-full p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Generate Tagihan Massal</h3>
            <button onclick="document.getElementById('generate-modal').classList.add('hidden')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('finance.bills.generate') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Periode (YYYY-MM)</label>
                <input type="month" name="period" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Komponen</label>
                <input type="text" name="component" value="SPP" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nominal Tagihan (Rp)</label>
                <input type="number" name="amount" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none" placeholder="Masukkan nominal rupiah">
            </div>
            <div class="flex items-center gap-2.5 py-1.5 border border-blue-100 bg-blue-50/40 rounded-xl px-3 text-blue-700">
                <input type="checkbox" name="auto_notify" id="auto_notify" value="1" checked class="rounded border-blue-300 text-blue-600 focus:ring-blue-500/20">
                <label for="auto_notify" class="text-xs font-semibold select-none cursor-pointer">Kirim Notifikasi Otomatis ke WhatsApp Orangtua</label>
            </div>
            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('generate-modal').classList.add('hidden')" class="px-5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-sm transition">Generate</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tambah Tagihan Individual -->
<div id="create-single-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-100 max-w-md w-full p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Tambah Tagihan Siswa</h3>
            <button onclick="document.getElementById('create-single-modal').classList.add('hidden')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form action="{{ route('finance.bills.store-single') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Siswa</label>
                <select name="student_id" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none bg-white">
                    <option value="">Pilih Siswa</option>
                    @foreach($students as $s)
                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Periode (YYYY-MM)</label>
                <input type="month" name="period" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Komponen</label>
                <input type="text" name="component" value="SPP" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nominal Tagihan (Rp)</label>
                <input type="number" name="amount" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none" placeholder="Masukkan nominal rupiah">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jatuh Tempo</label>
                <input type="date" name="due_date" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div class="flex items-center gap-2.5 py-1.5 border border-blue-100 bg-blue-50/40 rounded-xl px-3 text-blue-700">
                <input type="checkbox" name="auto_notify" id="auto_notify_single" value="1" checked class="rounded border-blue-300 text-blue-600 focus:ring-blue-500/20">
                <label for="auto_notify_single" class="text-xs font-semibold select-none cursor-pointer">Kirim Notifikasi Otomatis ke WhatsApp Orangtua</label>
            </div>
            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('create-single-modal').classList.add('hidden')" class="px-5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-sm transition">Simpan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Tagihan -->
<div id="edit-bill-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-100 max-w-md w-full p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Edit Detail Tagihan</h3>
            <button onclick="document.getElementById('edit-bill-modal').classList.add('hidden')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="edit-bill-form" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Komponen</label>
                <input type="text" name="component" id="edit-component" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Nominal Tagihan (Rp)</label>
                <input type="number" name="amount" id="edit-amount" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jatuh Tempo</label>
                <input type="date" name="due_date" id="edit-due-date" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none">
            </div>
            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('edit-bill-modal').classList.add('hidden')" class="px-5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 shadow-sm transition">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Payment -->
<div id="payment-modal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl border border-slate-100 max-w-md w-full p-6 space-y-4 shadow-xl">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="font-bold text-slate-900 text-lg">Catat Pembayaran SPP</h3>
            <button onclick="document.getElementById('payment-modal').classList.add('hidden')" class="p-1 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="payment-form" method="POST" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Jumlah Bayar (Rp)</label>
                <input type="number" name="amount" id="pay-amount" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none font-mono font-bold text-slate-900">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Tanggal Bayar</label>
                <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none text-slate-700">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Metode Pembayaran</label>
                <select name="method" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none bg-white font-medium text-slate-700">
                    <option value="cash">Tunai (Cash)</option>
                    <option value="transfer">Transfer Bank</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1.5">Referensi / Penyetor</label>
                <input type="text" name="reference" class="w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm focus:ring-2 focus:ring-blue-500/10 focus:border-blue-500 focus:outline-none" placeholder="No. referensi transfer atau nama penyetor">
            </div>
            <div class="flex justify-end gap-2.5 pt-3 border-t border-slate-100">
                <button type="button" onclick="document.getElementById('payment-modal').classList.add('hidden')" class="px-5 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-sm font-semibold transition">Batal</button>
                <button type="submit" class="px-5 py-2 rounded-xl bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 shadow-sm transition">Simpan Pembayaran</button>
            </div>
        </form>
    </div>
</div>

<script>
function openPayment(billId, remaining) {
    document.getElementById('payment-form').action = '/bills/' + billId + '/payment';
    document.getElementById('pay-amount').value = remaining;
    document.getElementById('payment-modal').classList.remove('hidden');
}

function openEditBill(billId, component, amount, dueDate) {
    document.getElementById('edit-bill-form').action = '/finance/bills/' + billId;
    document.getElementById('edit-component').value = component;
    document.getElementById('edit-amount').value = amount;
    document.getElementById('edit-due-date').value = dueDate;
    document.getElementById('edit-bill-modal').classList.remove('hidden');
}
</script>
@endsection
