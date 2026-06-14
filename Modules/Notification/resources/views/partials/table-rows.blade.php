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
