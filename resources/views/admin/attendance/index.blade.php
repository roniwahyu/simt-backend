@extends('layouts.app')
@section('title', 'Presensi')
@section('content')
<div class="space-y-4">
    <h1 class="text-2xl font-bold">Presensi Harian</h1>
    <form method="GET" class="flex flex-col sm:flex-row gap-3">
        <select name="class_id" class="border rounded px-3 py-2 bg-white" onchange="this.form.submit()">
            <option value="">Pilih Kelas</option>
            @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ $selectedClass == $c->id ? 'selected' : '' }}>
                {{ $c->name }} — {{ $c->schoolYear->name ?? '' }}
            </option>
            @endforeach
        </select>
        <input type="date" name="date" value="{{ $date }}" class="border rounded px-3 py-2" onchange="this.form.submit()">
    </form>

    @if($students && $students->count())
    <div class="bg-white rounded-lg shadow border border-gray-100">
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between">
            <span class="font-semibold">Kelas: {{ $students->first()->classes->first()->name ?? '' }} — {{ $date }}</span>
            <div class="text-sm text-gray-500">Default: <span class="font-medium text-emerald-600">Hadir</span>. Tap untuk ubah.</div>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 p-4" id="attendance-grid">
            @foreach($students as $s)
            @php
                $today = $s->attendance_today;
                $status = $today->status ?? 'H';
            @endphp
            <div class="border rounded-lg p-3 flex items-center justify-between cursor-pointer select-none attendance-card"
                 data-student-id="{{ $s->id }}"
                 data-status="{{ $status }}">
                <div>
                    <div class="font-medium">{{ $s->name }}</div>
                    <div class="text-xs text-gray-500">{{ $s->nis ?? '-' }}</div>
                </div>
                <span class="status-badge inline-flex px-2 py-1 rounded text-xs font-bold
                    {{ $status === 'H' ? 'bg-emerald-100 text-emerald-700' : '' }}
                    {{ $status === 'A' ? 'bg-red-100 text-red-700' : '' }}
                    {{ in_array($status, ['I','S']) ? 'bg-amber-100 text-amber-700' : '' }}
                    {{ $status === 'T' ? 'bg-blue-100 text-blue-700' : '' }}
                ">{{ \App\Models\Attendance::statusLabel($status) }}</span>
            </div>
            @endforeach
        </div>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between">
            <div class="text-sm text-gray-500" id="status-text">Belum ada perubahan</div>
            <button id="save-btn" class="px-4 py-2 rounded bg-blue-600 text-white text-sm hover:bg-blue-700 disabled:opacity-50" disabled onclick="saveAttendance()">Simpan Presensi</button>
        </div>
    </div>
    @else
    <div class="bg-white rounded-lg shadow border border-gray-100 p-8 text-center text-gray-500">
        Pilih kelas dan tanggal untuk mulai presensi.
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const statusOrder = ['H','A','I','S','T'];
const statusLabels = {H:'Hadir',A:'Alpa',I:'Izin',S:'Sakit',T:'Terlambat'};
const statusClasses = {
    H:'bg-emerald-100 text-emerald-700',
    A:'bg-red-100 text-red-700',
    I:'bg-amber-100 text-amber-700',
    S:'bg-amber-100 text-amber-700',
    T:'bg-blue-100 text-blue-700',
};

document.querySelectorAll('.attendance-card').forEach(card => {
    card.addEventListener('click', () => {
        let current = card.dataset.status;
        let nextIdx = (statusOrder.indexOf(current) + 1) % statusOrder.length;
        let next = statusOrder[nextIdx];
        card.dataset.status = next;
        const badge = card.querySelector('.status-badge');
        badge.textContent = statusLabels[next];
        badge.className = 'status-badge inline-flex px-2 py-1 rounded text-xs font-bold ' + statusClasses[next];
        document.getElementById('save-btn').disabled = false;
        document.getElementById('status-text').textContent = 'Ada perubahan — simpan untuk kirim notifikasi WA.';
    });
});

async function saveAttendance() {
    const btn = document.getElementById('save-btn');
    btn.disabled = true;
    btn.textContent = 'Menyimpan...';

    const records = Array.from(document.querySelectorAll('.attendance-card')).map(card => ({
        student_id: card.dataset.studentId,
        status: card.dataset.status,
    }));

    const res = await fetch('{{ route("attendance.store") }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        },
        body: JSON.stringify({
            class_id: {{ $selectedClass ?? 'null' }},
            date: '{{ $date }}',
            records,
        }),
    });

    const data = await res.json();
    if (data.success) {
        alert(data.message || 'Presensi tersimpan.');
        location.reload();
    } else {
        alert('Gagal: ' + (data.message || 'Terjadi kesalahan'));
        btn.disabled = false;
        btn.textContent = 'Simpan Presensi';
    }
}
</script>
@endpush
