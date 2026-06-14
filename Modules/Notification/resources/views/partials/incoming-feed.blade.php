@forelse($incomingMessages as $msg)
@php
    $payload = $msg->payload;
    $messageText = $payload['message'] ?? '';
    $senderName = $payload['sender_name'] ?? 'Wali Murid';
    $messageId = $payload['message_id'] ?? null;
    $tenantName = $msg->tenant?->name ?? 'Tenant Utama';
@endphp
<div class="p-4 bg-slate-900 border border-slate-800 rounded-xl space-y-2 hover:border-slate-700 transition duration-300">
    <div class="flex items-center justify-between">
        <div>
            <div class="font-bold text-slate-200 text-sm flex items-center space-x-1.5">
                <span>{{ $senderName }}</span>
                <span class="text-[10px] px-2 py-0.5 rounded bg-emerald-500/10 border border-emerald-500/20 text-emerald-400">
                    {{ $tenantName }}
                </span>
            </div>
            <div class="text-xs text-slate-400 font-medium mt-0.5">+{{ $msg->to_phone }}</div>
        </div>
        <span class="text-[10px] text-slate-500 font-medium">
            {{ $msg->created_at->diffForHumans() }}
        </span>
    </div>
    
    <div class="text-xs text-slate-300 bg-slate-950/40 p-3 rounded-lg border border-slate-900/60 leading-relaxed font-medium wa-message-content" data-raw-text="{{ $messageText }}">
        <!-- Will be parsed to HTML by client-side WA formatting script -->
        {{ $messageText }}
    </div>

    @if($messageId)
    <div class="text-[9px] text-slate-600 font-mono select-all">
        ID: {{ $messageId }}
    </div>
    @endif

    <div class="flex justify-end pt-1">
        <button onclick="replyTo('{{ $msg->to_phone }}', '{{ $msg->tenant_id }}', '{{ addslashes($senderName) }}', '{{ addslashes($messageText) }}')" 
            class="inline-flex items-center space-x-1.5 px-3 py-1 bg-blue-600/10 hover:bg-blue-600 border border-blue-500/20 hover:border-blue-500 text-blue-400 hover:text-white rounded-lg text-xs font-semibold transition duration-300">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
            </svg>
            <span>Balas</span>
        </button>
    </div>
</div>
@empty
<div class="py-12 text-center text-slate-500 text-sm bg-slate-900/40 border border-slate-800/80 rounded-xl">
    <svg class="w-8 h-8 mx-auto text-slate-600 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
    </svg>
    <span>Belum ada pesan masuk.</span>
</div>
@endforelse
