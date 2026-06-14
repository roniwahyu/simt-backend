<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $payment->receipt_no }}</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 14px; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 24px; border: 1px solid #ddd; }
        .header { text-align: center; border-bottom: 2px solid #1e40af; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; color: #1e40af; }
        .header p { margin: 4px 0; font-size: 12px; color: #666; }
        .meta { margin-bottom: 16px; }
        .meta-row { display: flex; justify-content: space-between; margin-bottom: 6px; }
        .meta-label { font-weight: bold; color: #555; }
        .total { font-size: 18px; font-weight: bold; color: #1e40af; margin-top: 16px; padding: 12px; background: #f3f4f6; border-radius: 6px; text-align: center; }
        .footer { margin-top: 24px; font-size: 11px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 12px; }
        .stamp { margin-top: 20px; text-align: right; font-size: 12px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $tenant->name ?? 'SIMT MTs' }}</h1>
            <p>Sistem Informasi Manajemen Terpadu</p>
            <p>{{ $tenant->address ?? '-' }}</p>
        </div>

        <div class="meta">
            <div class="meta-row"><span class="meta-label">No. Kwitansi:</span> <span>{{ $payment->receipt_no }}</span></div>
            <div class="meta-row"><span class="meta-label">Tanggal:</span> <span>{{ \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d F Y') }}</span></div>
            <div class="meta-row"><span class="meta-label">Siswa:</span> <span>{{ $payment->student->name ?? '-' }}</span></div>
            <div class="meta-row"><span class="meta-label">Komponen:</span> <span>{{ $payment->bill->component ?? '-' }} ({{ $payment->bill->period ?? '-' }})</span></div>
            <div class="meta-row"><span class="meta-label">Metode:</span> <span>{{ strtoupper($payment->method) }}</span></div>
            @if($payment->reference)
            <div class="meta-row"><span class="meta-label">Referensi:</span> <span>{{ $payment->reference }}</span></div>
            @endif
        </div>

        <div class="total">
            Rp {{ number_format($payment->amount, 0, ',', '.') }}
        </div>

        <div class="stamp">
            Diterima oleh,<br><br><br>
            <strong>{{ $payment->recorder->name ?? 'Bendahara' }}</strong><br>
            {{ now()->translatedFormat('d F Y') }}
        </div>

        <div class="footer">
            Kwitansi ini dicetak dari SIMT MVP. Simpan sebagai bukti pembayaran yang sah.<br>
            {{ $tenant->name ?? 'SIMT MVP' }} — {{ $tenant->phone ?? '-' }}
        </div>
    </div>
</body>
</html>
