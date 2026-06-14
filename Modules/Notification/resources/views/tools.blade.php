@extends('layouts.app')
@section('title', 'WhatsApp Tools')

@push('styles')
<!-- EasyMDE Markdown Editor -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
<style>
    .EasyMDEContainer .CodeMirror {
        background-color: #0f172a !important;
        color: #f1f5f9 !important;
        border-color: #334155 !important;
        border-bottom-left-radius: 12px !important;
        border-bottom-right-radius: 12px !important;
        font-family: monospace;
    }
    .editor-toolbar {
        background-color: #1e293b !important;
        border-color: #334155 !important;
        border-top-left-radius: 12px !important;
        border-top-right-radius: 12px !important;
    }
    .editor-toolbar button {
        color: #94a3b8 !important;
    }
    .editor-toolbar button:hover, .editor-toolbar button.active {
        color: #ffffff !important;
        background-color: #334155 !important;
    }
</style>
@endpush

@section('content')
<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">WhatsApp Broadcast & Live Tools</h1>
            <p class="text-sm text-gray-500">Kirim pesan WhatsApp manual dan pantau balasan masuk secara real-time dengan editor Markdown.</p>
        </div>
        <span class="inline-flex px-3 py-1 rounded-full text-xs font-bold leading-5 bg-blue-100 text-blue-800">
            WYSIWYG Markdown Enabled
        </span>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
        
        <!-- Left Side: Send Form (col-span-5) -->
        <div class="lg:col-span-5 bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-6">
            <h3 class="text-lg font-bold text-gray-900 border-b pb-3 flex items-center space-x-2">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                </svg>
                <span>Kirim Pesan Baru</span>
            </h3>

            <form id="wa-send-form" class="space-y-4">
                @csrf
                <!-- Tenant Selector -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Pilih Sekolah (Tenant)</label>
                    <select name="tenant_id" id="tenant-select" required class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300">
                        @foreach($tenants as $tenant)
                            <option value="{{ $tenant->id }}" {{ $tenant->id == $tenantId ? 'selected' : '' }}>
                                {{ $tenant->name }} ({{ $tenant->domain }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Recipient HP -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">No. WhatsApp Tujuan</label>
                    <input type="text" name="to_phone" id="to-phone-input" required 
                        class="w-full bg-slate-50 border border-gray-200 rounded-xl px-3.5 py-2.5 text-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition duration-300"
                        placeholder="Contoh: 62812xxxxxxx">
                </div>

                <!-- Markdown Editor -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider">Isi Pesan (Markdown)</label>
                    <textarea id="markdown-editor" name="message"></textarea>
                </div>

                <!-- Raw output hidden value to send -->
                <input type="hidden" name="raw_message" id="wa-raw-output">

                <button type="submit" id="send-btn" 
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 rounded-xl transition duration-300 shadow-md shadow-blue-500/10 active:scale-[0.99] flex items-center justify-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <span>Kirim via WhatsApp</span>
                </button>
            </form>
        </div>

        <!-- Right Side: Chat Feed & Mock WA Preview (col-span-7) -->
        <div class="lg:col-span-7 space-y-6">
            
            <!-- WhatsApp Live Preview Bubble -->
            <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <h3 class="text-sm font-bold text-slate-300 flex items-center space-x-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-green-500 animate-pulse"></span>
                        <span>Pratinjau Format WhatsApp (Real-time)</span>
                    </h3>
                    <span class="text-[10px] text-slate-500 font-mono">Mock Device</span>
                </div>
                
                <!-- Chat Screen wrapper -->
                <div class="bg-[#0b141a] rounded-xl p-4 min-h-[120px] flex items-end justify-end relative overflow-hidden" 
                     style="background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); background-repeat: repeat;">
                    <!-- Ambient color layer -->
                    <div class="absolute inset-0 bg-[#0b141a]/90 mix-blend-multiply pointer-events-none"></div>
                    
                    <!-- WhatsApp Green Bubble -->
                    <div class="relative z-10 bg-[#005c4b] text-[#e9edef] rounded-lg rounded-tr-none px-4 py-2.5 shadow max-w-[85%] text-sm space-y-1">
                        <div id="wa-bubble-preview" class="leading-relaxed font-medium break-words">
                            <em>(Pesan kosong. Ketik sesuatu di editor sebelah kiri...)</em>
                        </div>
                        <div class="text-[9px] text-[#8696a0] text-right">
                            21:40 &nbsp;✓✓
                        </div>
                    </div>
                </div>
            </div>

            <!-- Incoming Messages Chat Inbox -->
            <div class="bg-slate-950 rounded-2xl border border-slate-800 p-6 flex flex-col space-y-4">
                <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z" />
                        </svg>
                        <h3 class="text-sm font-bold text-slate-300">Pesan Masuk Terkini (Real-time Feed)</h3>
                    </div>
                    <span class="text-[10px] bg-emerald-500/10 text-emerald-400 border border-emerald-500/20 px-2.5 py-0.5 rounded-full font-semibold">
                        Auto Update (5s)
                    </span>
                </div>

                <!-- Live Feed container -->
                <div id="incoming-feed-container" class="space-y-4 max-h-[350px] overflow-y-auto pr-1">
                    @include('notification::partials.incoming-feed')
                </div>
            </div>

        </div>

    </div>
</div>
@endsection

@push('scripts')
<!-- EasyMDE Javascript -->
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script>
    // Parsers untuk Markdown ke WhatsApp formatting
    function markdownToWhatsApp(markdown) {
        let text = markdown;

        // Header conversion to bold plain text
        text = text.replace(/^(#{1,6})\s+(.+)$/gm, '*$2*');

        // Bold formatting conversion
        text = text.replace(/\*\*(.*?)\*\*/g, '§B$1§B');
        text = text.replace(/__(.*?)__/g, '§B$1§B');
        text = text.replace(/\*(.*?)\*/g, '_$1_');
        text = text.replace(/_([^_]+)_/g, '_$1_');
        text = text.replace(/§B(.*?)§B/g, '*$1*');

        // Strikethrough conversion
        text = text.replace(/~~(.*?)~~/g, '~$1~');

        // Unordered lists to bullet points
        text = text.replace(/^\s*[-*+]\s+(.+)$/gm, '• $1');

        return text;
    }

    // Parsers WhatsApp formatting ke HTML (Bold, Italic, Strike, Code)
    function whatsappToHtml(text) {
        let html = text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;");

        // Bold *text*
        html = html.replace(/\*(.*?)\*/g, '<strong>$1</strong>');

        // Italic _text_
        html = html.replace(/_(.*?)_/g, '<em>$1</em>');

        // Strikethrough ~text~
        html = html.replace(/~(.*?)~/g, '<del>$1</del>');

        // Monospace ```code```
        html = html.replace(/```(.*?)```/g, '<code class="bg-[#202c33] px-1 py-0.5 rounded text-rose-400 font-mono text-xs">$1</code>');

        // Line breaks
        html = html.replace(/\n/g, '<br>');

        return html;
    }

    // Inisialisasi Editor EasyMDE
    const easyMDE = new EasyMDE({
        element: document.getElementById('markdown-editor'),
        spellChecker: false,
        placeholder: "Tulis pesan Anda menggunakan Markdown...",
        status: false,
        maxHeight: "150px",
        toolbar: ["bold", "italic", "|", "quote", "unordered-list", "ordered-list", "|", "preview"]
    });

    // Event listener saat mengetik
    easyMDE.codemirror.on("change", () => {
        updateLivePreview();
    });

    function updateLivePreview() {
        const markdown = easyMDE.value();
        const waText = markdownToWhatsApp(markdown);
        
        // Simpan versi WhatsApp murni ke input hidden
        document.getElementById('wa-raw-output').value = waText;
        
        // Perbarui balon pratinjau chat WhatsApp
        if (waText.trim()) {
            document.getElementById('wa-bubble-preview').innerHTML = whatsappToHtml(waText);
        } else {
            document.getElementById('wa-bubble-preview').innerHTML = '<em>(Pesan kosong. Ketik sesuatu di editor sebelah kiri...)</em>';
        }
    }

    // Handler ketika tombol balas ditekan
    function replyTo(phone, tenantId, senderName, messageText) {
        // Isi input
        document.getElementById('to-phone-input').value = phone;
        document.getElementById('tenant-select').value = tenantId;
        
        // Masukkan balasan quote ke editor
        const quote = `> *${senderName}*: ${messageText}\n\n`;
        easyMDE.value(quote);
        
        // Fokuskan editor
        easyMDE.codemirror.focus();
        
        // Pindahkan kursor ke baris paling akhir
        easyMDE.codemirror.setCursor(easyMDE.codemirror.lineCount(), 0);
        
        // Scroll halus ke form
        document.getElementById('wa-send-form').scrollIntoView({ behavior: 'smooth' });
    }

    // Submit handler via AJAX
    document.getElementById('wa-send-form').addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const sendBtn = document.getElementById('send-btn');
        const originalText = sendBtn.innerHTML;
        
        // Ubah tombol ke state loading
        sendBtn.disabled = true;
        sendBtn.innerHTML = `
            <svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            <span>Mengirim...</span>
        `;
        
        const tenantId = document.getElementById('tenant-select').value;
        const toPhone = document.getElementById('to-phone-input').value;
        const message = document.getElementById('wa-raw-output').value;
        
        try {
            const res = await fetch('{{ route("notification.tools.send") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    tenant_id: tenantId,
                    to_phone: toPhone,
                    message: message
                })
            });
            
            const data = await res.json();
            
            if (res.ok && data.success) {
                alert(data.message);
                easyMDE.value('');
                document.getElementById('to-phone-input').value = '';
                updateLivePreview();
                // Muat ulang feed secara langsung
                loadIncomingFeed();
            } else {
                alert(data.message || 'Gagal mengirim pesan.');
            }
        } catch (err) {
            alert('Terjadi kesalahan saat menghubungi server.');
        } finally {
            sendBtn.disabled = false;
            sendBtn.innerHTML = originalText;
        }
    });

    // Fungsi untuk memuat ulang daftar pesan masuk secara AJAX
    async function loadIncomingFeed() {
        try {
            const res = await fetch('{{ route("notification.incoming_feed") }}');
            if (res.ok) {
                const html = await res.text();
                document.getElementById('incoming-feed-container').innerHTML = html;
                parseAllIncomingMessages();
            }
        } catch (e) {
            // Suppress errors silently
        }
    }

    // Parser seluruh pesan masuk di daftar
    function parseAllIncomingMessages() {
        document.querySelectorAll('.wa-message-content').forEach(element => {
            const rawText = element.getAttribute('data-raw-text') || element.textContent.trim();
            element.innerHTML = whatsappToHtml(rawText);
        });
    }

    // Jalankan pemformatan saat pertama kali halaman terbuka
    document.addEventListener("DOMContentLoaded", () => {
        parseAllIncomingMessages();
    });

    // Jalankan pemungutan (polling) pesan masuk secara otomatis tiap 5 detik
    setInterval(loadIncomingFeed, 5000);
</script>
@endpush
