<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi {{ $payment->receipt_no }}</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 14px; color: #333; line-height: 1.4; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; }
        .header { text-align: center; border-bottom: 2px solid #1e40af; padding-bottom: 8px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; color: #1e40af; }
        .header p { margin: 3px 0; font-size: 11px; color: #666; }
        .meta-table { width: 100%; margin-bottom: 20px; border-collapse: collapse; }
        .meta-table td { padding: 4px 0; vertical-align: top; }
        .meta-label { font-weight: bold; color: #555; width: 140px; }
        .meta-value { color: #111; }
        .total-box { font-size: 20px; font-weight: bold; color: #1e40af; margin-top: 15px; padding: 12px; background: #f3f4f6; border-radius: 6px; text-align: center; border: 1px dashed #1e40af; }
        .signature-table { width: 100%; margin-top: 30px; }
        .signature-table td { text-align: right; font-size: 12px; }
        .footer { margin-top: 30px; font-size: 10px; color: #888; text-align: center; border-top: 1px solid #eee; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{ $tenant->name ?? 'SIMT MTs' }}</h1>
            <p>Sistem Informasi Manajemen Terpadu</p>
            <p>{{ $tenant->address ?? '-' }}</p>
        </div>

        <table class="meta-table">
            <tr>
                <td class="meta-label">No. Kwitansi</td>
                <td style="width: 10px;">:</td>
                <td class="meta-value"><strong>{{ $payment->receipt_no }}</strong></td>
            </tr>
            <tr>
                <td class="meta-label">Tanggal Bayar</td>
                <td>:</td>
                <td class="meta-value">{{ \Carbon\Carbon::parse($payment->payment_date)->translatedFormat('d F Y') }}</td>
            </tr>
            <tr>
                <td class="meta-label">Nama Siswa</td>
                <td>:</td>
                <td class="meta-value">{{ $payment->student->name ?? '-' }}</td>
            </tr>
            <tr>
                <td class="meta-label">Komponen / Periode</td>
                <td>:</td>
                <td class="meta-value">{{ $payment->bill->component ?? 'SPP' }} ({{ $payment->bill->period ?? '-' }})</td>
            </tr>
            <tr>
                <td class="meta-label">Metode Pembayaran</td>
                <td>:</td>
                <td class="meta-value">{{ strtoupper($payment->method) }}</td>
            </tr>
            @if($payment->reference)
            <tr>
                <td class="meta-label">Referensi/Penyetor</td>
                <td>:</td>
                <td class="meta-value">{{ $payment->reference }}</td>
            </tr>
            @endif
        </table>

        <div class="total-box">
            Rp {{ number_format($payment->amount, 0, ',', '.') }}
        </div>

        <table class="signature-table">
            <tr>
                <td>
                    Diterima oleh,<br><br><br><br>
                    <strong>{{ $payment->recorder->name ?? 'Bendahara' }}</strong><br>
                    SIMT Finance Office
                </td>
            </tr>
        </table>

        <div class="footer">
            Kwitansi ini dicetak dari SIMT secara otomatis dan merupakan bukti pembayaran yang sah.<br>
            {{ $tenant->name ?? 'SIMT MVP' }} — {{ $tenant->phone ?? '-' }}
        </div>
    </div>
</body>
</html>
