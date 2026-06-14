@extends('layouts.app')
@section('title', 'Tagihan SPP')
@section('content')
<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold">Tagihan & Pembayaran SPP</h1>
        <button onclick="document.getElementById('generate-modal').classList.remove('hidden')" class="px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Generate Tagihan Massal</button>
    </div>

    <form method="GET" class="flex flex-wrap gap-3 items-end">
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Siswa</label>
            <select name="student_id" class="border rounded px-3 py-2 text-sm bg-white">
                <option value="">Semua</option>
                @foreach($students as $s)
                <option value="{{ $s->id }}" {{ request('student_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
            <select name="status" class="border rounded px-3 py-2 text-sm bg-white">
                <option value="">Semua</option>
                <option value="unpaid" {{ request('status') == 'unpaid' ? 'selected' : '' }}>Belum Lunas</option>
                <option value="partial" {{ request('status') == 'partial' ? 'selected' : '' }}>Parsial</option>
                <option value="paid" {{ request('status') == 'paid' ? 'selected' : '' }}>Lunas</option>
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-600 mb-1">Periode</label>
            <input type="month" name="period" value="{{ request('period') }}" class="border rounded px-3 py-2 text-sm">
        </div>
        <button type="submit" class="px-3 py-2 rounded bg-gray-100 hover:bg-gray-200 text-sm">Filter</button>
    </form>

    <div class="bg-white rounded-lg shadow border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 text-left">Siswa</th>
                        <th class="px-4 py-2 text-left">Periode</th>
                        <th class="px-4 py-2 text-right">Tagihan</th>
                        <th class="px-4 py-2 text-right">Dibayar</th>
                        <th class="px-4 py-2 text-center">Status</th>
                        <th class="px-4 py-2 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($bills as $b)
                    <tr class="border-t">
                        <td class="px-4 py-2">{{ $b->student->name ?? '-' }}</td>
                        <td class="px-4 py-2">{{ $b->period }}</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-right">Rp {{ number_format($b->paid_amount, 0, ',', '.') }}</td>
                        <td class="px-4 py-2 text-center">
                            <span class="px-2 py-1 rounded text-xs font-medium
                                {{ $b->status === 'paid' ? 'bg-emerald-100 text-emerald-700' : '' }}
                                {{ $b->status === 'unpaid' ? 'bg-red-100 text-red-700' : '' }}
                                {{ $b->status === 'partial' ? 'bg-amber-100 text-amber-700' : '' }}
                            ">{{ $b->status }}</span>
                        </td>
                        <td class="px-4 py-2 text-right space-x-2">
                            @if($b->status !== 'paid')
                            <button onclick="openPayment({{ $b->id }}, {{ $b->remaining() }})" class="text-blue-600 hover:underline text-xs">Bayar</button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-gray-100">{{ $bills->links() }}</div>
    </div>
</div>

<!-- Modal Generate -->
<div id="generate-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 space-y-4">
        <h3 class="font-semibold">Generate Tagihan Massal</h3>
        <form action="{{ route('finance.bills.generate') }}" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Periode (YYYY-MM)</label>
                <input type="month" name="period" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Komponen</label>
                <input type="text" name="component" value="SPP" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Nominal (Rp)</label>
                <input type="number" name="amount" required class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('generate-modal').classList.add('hidden')" class="px-3 py-2 rounded border text-sm">Batal</button>
                <button type="submit" class="px-3 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700">Generate</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Payment -->
<div id="payment-modal" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-lg max-w-md w-full p-6 space-y-4">
        <h3 class="font-semibold">Catat Pembayaran</h3>
        <form id="payment-form" method="POST" class="space-y-3">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Jumlah Bayar</label>
                <input type="number" name="amount" id="pay-amount" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Tanggal Bayar</label>
                <input type="date" name="payment_date" value="{{ now()->toDateString() }}" required class="w-full border rounded px-3 py-2">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Metode</label>
                <select name="method" class="w-full border rounded px-3 py-2">
                    <option value="cash">Tunai</option>
                    <option value="transfer">Transfer</option>
                </select>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Referensi</label>
                <input type="text" name="reference" class="w-full border rounded px-3 py-2">
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="document.getElementById('payment-modal').classList.add('hidden')" class="px-3 py-2 rounded border text-sm">Batal</button>
                <button type="submit" class="px-3 py-2 rounded bg-emerald-600 text-white text-sm hover:bg-emerald-700">Simpan</button>
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
</script>
@endsection
