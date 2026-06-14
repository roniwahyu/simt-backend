@extends('layouts.app')
@section('title', 'WhatsApp Connect')
@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold">Koneksi WhatsApp Gateway</h1>
            <p class="text-sm text-gray-500">Hubungkan nomor WhatsApp sekolah Anda untuk mengirimkan notifikasi presensi dan keuangan.</p>
        </div>
        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold leading-5 id-badge bg-blue-100 text-blue-800">
            Engine: Baileys (WebSocket)
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Status & QR Code Box -->
        <div class="lg:col-span-1 bg-white rounded-lg shadow border border-gray-100 p-6 flex flex-col items-center justify-center text-center">
            <h2 class="text-lg font-semibold mb-4 text-gray-700">Status Koneksi</h2>

            <!-- State: Disconnected -->
            <div id="state-disconnected" class="{{ $status === 'DISCONNECTED' ? '' : 'hidden' }} space-y-4 flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-red-100 flex items-center justify-center text-red-600 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </div>
                <div class="text-xl font-bold text-red-600">Terputus</div>
                <p class="text-xs text-gray-400 px-4">Nomor WhatsApp sekolah belum terhubung ke gateway.</p>
                <button onclick="startConnection()" class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg text-sm transition-colors shadow">
                    Hubungkan WhatsApp
                </button>
            </div>

            <!-- State: Connecting / Loading -->
            <div id="state-connecting" class="{{ $status === 'CONNECTING' ? '' : 'hidden' }} space-y-4 flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600 mb-2"></div>
                <div class="text-lg font-medium text-gray-500">Menghubungkan...</div>
                <p class="text-xs text-gray-400">Sedang menginisialisasi sesi WhatsApp. Silakan tunggu.</p>
            </div>

            <!-- State: QR Ready -->
            <div id="state-qr" class="{{ $status === 'QR_READY' ? '' : 'hidden' }} space-y-4 flex flex-col items-center">
                <div class="text-sm font-medium text-gray-600 mb-1">Pindai QR Code di bawah:</div>
                <div class="p-3 bg-white border rounded-lg shadow-sm">
                    <img id="qr-img" src="{{ $qr ?? '' }}" alt="Scan QR Code" class="w-48 h-48 {{ $qr ? '' : 'hidden' }}">
                    <div id="qr-loader" class="w-48 h-48 flex items-center justify-center bg-gray-50 text-xs text-gray-400 {{ $qr ? 'hidden' : '' }}">
                        Menyiapkan QR...
                    </div>
                </div>
                <p class="text-xs text-gray-400 max-w-xs">Buka WhatsApp di HP Anda > Perangkat Tertaut > Tautkan Perangkat, lalu arahkan kamera ke kode QR.</p>
                <button onclick="stopConnection()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-600 text-xs font-semibold rounded-lg">
                    Batalkan
                </button>
            </div>

            <!-- State: Connected -->
            <div id="state-connected" class="{{ $status === 'CONNECTED' ? '' : 'hidden' }} space-y-4 flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center text-emerald-600 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div class="text-xl font-bold text-emerald-600">Terhubung</div>
                <div class="text-sm font-semibold text-gray-700" id="connected-num">{{ $number ? '+'.$number : '' }}</div>
                <p class="text-xs text-emerald-600 px-4 font-medium bg-emerald-50 py-1.5 rounded-full">Sistem aktif & siap mengirim notifikasi.</p>
                <button onclick="stopConnection()" class="px-5 py-2.5 bg-red-50 hover:bg-red-100 text-red-600 font-semibold rounded-lg text-sm transition-colors border border-red-200">
                    Putuskan Koneksi
                </button>
            </div>

            <!-- State: Gateway Error -->
            <div id="state-error" class="{{ $status === 'GATEWAY_ERROR' ? '' : 'hidden' }} space-y-4 flex flex-col items-center">
                <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center text-amber-600 mb-2">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div class="text-lg font-bold text-amber-600">Gateway Offline</div>
                <p class="text-xs text-gray-400 px-4">Gagal menghubungkan ke WA Gateway. Pastikan servis Node.js di server Anda sudah dijalankan.</p>
                <button onclick="location.reload()" class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg">
                    Coba Lagi
                </button>
            </div>
        </div>

        <!-- Log Antrean Notifikasi WA Terakhir -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow border border-gray-100 p-6 flex flex-col">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-700">Antrean Notifikasi Terakhir</h2>
                <span class="text-xs text-gray-400 bg-gray-100 px-2.5 py-1 rounded-full font-medium">10 Data Terbaru</span>
            </div>
            
            <div class="flex-1 overflow-x-auto">
                <table class="min-w-full text-xs text-left text-gray-500">
                    <thead class="bg-gray-50 text-gray-700 uppercase font-semibold border-b border-gray-200">
                        <tr>
                            <th class="px-4 py-3">No. HP</th>
                            <th class="px-4 py-3 text-center">Arah</th>
                            <th class="px-4 py-3">Tipe</th>
                            <th class="px-4 py-3">Pesan</th>
                            <th class="px-4 py-3">Status</th>
                            <th class="px-4 py-3">Waktu</th>
                        </tr>
                    </thead>
                    <tbody id="notifications-table-body" class="divide-y divide-gray-100">
                        @forelse($recentNotifications as $notif)
                        @php
                            $isIncoming = $notif->type === 'incoming';
                            $messageText = '';
                            $payload = $notif->payload;
                            if ($isIncoming) {
                                $messageText = $payload['message'] ?? '';
                            } else {
                                if ($notif->type === 'attendance') {
                                    $messageText = "Presensi: " . ($payload['student_name'] ?? '') . " (" . ($payload['status'] ?? '') . ")";
                                } elseif ($notif->type === 'bill_reminder') {
                                    $messageText = "Tagihan: " . ($payload['student_name'] ?? '') . " - Rp " . number_format($payload['amount'] ?? 0, 0, ',', '.');
                                } elseif ($notif->type === 'credential') {
                                    $messageText = "Akun Portal: " . ($payload['phone'] ?? '');
                                } else {
                                    $messageText = $payload['message'] ?? 'Pesan kustom';
                                }
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-semibold text-gray-900">
                                {{ $notif->to_phone }}
                                @if($isIncoming && !empty($payload['sender_name']))
                                    <span class="block text-[10px] text-gray-400 font-normal">{{ $payload['sender_name'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($isIncoming)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <svg class="w-3 h-3 mr-1 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 13l-7 7-7-7m14-6l-7 7-7-7"/></svg>
                                    Masuk
                                </span>
                                @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    <svg class="w-3 h-3 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 11l7-7 7 7M5 19l7-7 7 7"/></svg>
                                    Keluar
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 capitalize">
                                <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700 border">
                                    {{ str_replace('_', ' ', $notif->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 max-w-xs truncate font-medium text-gray-600" title="{{ $messageText }}">
                                <span>{{ $messageText }}</span>
                                @if(!empty($payload['message_id']))
                                    <span class="block text-[9px] text-gray-400 font-mono tracking-tighter">ID: {{ $payload['message_id'] }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold
                                    {{ $notif->status === 'delivered' ? 'bg-emerald-100 text-emerald-800' : '' }}
                                    {{ $notif->status === 'sent' ? 'bg-blue-100 text-blue-800' : '' }}
                                    {{ $notif->status === 'retrying' ? 'bg-amber-100 text-amber-800' : '' }}
                                    {{ $notif->status === 'failed' ? 'bg-red-100 text-red-800' : '' }}
                                ">
                                    {{ ucfirst($notif->status) }}
                                </span>
                                @if($notif->last_error)
                                    <span class="block text-[10px] text-red-500 truncate max-w-[150px]" title="{{ $notif->last_error }}">{{ $notif->last_error }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-400 font-medium">{{ $notif->created_at->diffForHumans() }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-400">Belum ada antrean notifikasi yang tercatat.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
let pollInterval = null;

function showState(state) {
    document.getElementById('state-disconnected').classList.add('hidden');
    document.getElementById('state-connecting').classList.add('hidden');
    document.getElementById('state-qr').classList.add('hidden');
    document.getElementById('state-connected').classList.add('hidden');
    document.getElementById('state-error').classList.add('hidden');

    if (state === 'DISCONNECTED') document.getElementById('state-disconnected').classList.remove('hidden');
    else if (state === 'CONNECTING') document.getElementById('state-connecting').classList.remove('hidden');
    else if (state === 'QR_READY') document.getElementById('state-qr').classList.remove('hidden');
    else if (state === 'CONNECTED') document.getElementById('state-connected').classList.remove('hidden');
    else document.getElementById('state-error').classList.remove('hidden');
}

async function startConnection() {
    showState('CONNECTING');
    try {
        const res = await fetch('{{ route("notification.session.start") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            if (data.status === 'QR_READY') {
                showState('QR_READY');
                if (data.qr) {
                    document.getElementById('qr-img').src = data.qr;
                    document.getElementById('qr-img').classList.remove('hidden');
                    document.getElementById('qr-loader').classList.add('hidden');
                }
            } else if (data.status === 'CONNECTED') {
                showState('CONNECTED');
            }
            startPolling();
        } else {
            showState('ERROR');
        }
    } catch (e) {
        showState('ERROR');
    }
}

async function stopConnection() {
    if (!confirm('Apakah Anda yakin ingin memutuskan koneksi WhatsApp?')) return;
    
    showState('CONNECTING');
    try {
        const res = await fetch('{{ route("notification.session.stop") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await res.json();
        if (data.success) {
            showState('DISCONNECTED');
            stopPolling();
        } else {
            showState('ERROR');
        }
    } catch (e) {
        showState('ERROR');
    }
}

function startPolling() {
    if (pollInterval) clearInterval(pollInterval);
    pollInterval = setInterval(checkStatus, 3000);
}

function stopPolling() {
    if (pollInterval) {
        clearInterval(pollInterval);
        pollInterval = null;
    }
}

async function checkStatus() {
    try {
        const res = await fetch('{{ route("notification.session.status") }}');
        const data = await res.json();
        if (data.success) {
            if (data.status === 'CONNECTED') {
                showState('CONNECTED');
                if (data.number) {
                    document.getElementById('connected-num').textContent = '+' + data.number;
                }
                stopPolling();
                setTimeout(() => location.reload(), 1500); // Reload to update table
            } else if (data.status === 'QR_READY') {
                showState('QR_READY');
                if (data.qr) {
                    document.getElementById('qr-img').src = data.qr;
                    document.getElementById('qr-img').classList.remove('hidden');
                    document.getElementById('qr-loader').classList.add('hidden');
                }
            } else if (data.status === 'DISCONNECTED') {
                showState('DISCONNECTED');
                stopPolling();
            }
        }
    } catch (e) {
        // Suppress gateway poll errors silently
    }
}

// Automatically start polling if the page loads in Connecting or QR_READY state
const initialStatus = '{{ $status }}';
if (initialStatus === 'CONNECTING' || initialStatus === 'QR_READY') {
    startPolling();
}

// Poll the notifications table every 5 seconds to keep incoming/outgoing lists loaded and fresh
setInterval(async () => {
    try {
        const res = await fetch('{{ route("notification.table") }}');
        if (res.ok) {
            const html = await res.text();
            document.getElementById('notifications-table-body').innerHTML = html;
        }
    } catch (e) {
        // Suppress list poll errors silently
    }
}, 5000);
</script>
@endpush
