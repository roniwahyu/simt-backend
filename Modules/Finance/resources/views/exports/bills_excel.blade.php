<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Tagihan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 20px; }
        th, td { border: 1px solid #333; padding: 6px 8px; text-align: left; }
        th { background: #1e40af; color: #fff; font-weight: bold; }
        .summary { background: #f3f4f6; font-weight: bold; }
        .paid { background: #e2f0d9; }
        .partial { background: #fff2cc; }
        .unpaid { background: #f8cbad; }
        .right { text-align: right; }
        .center { text-align: center; }
    </style>
</head>
<body>
    <h2 style="margin: 0 0 4px 0;">Rekap Tagihan dan Pembayaran</h2>
    @if(!empty($filters['period']))
        <p style="margin: 0 0 4px 0;">Periode: {{ $filters['period'] }}</p>
    @endif
    @if(!empty($filters['status']))
        <p style="margin: 0 0 8px 0;">Status: {{ ucfirst($filters['status']) }}</p>
    @endif

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 30px;">No</th>
                <th>Siswa</th>
                <th>Periode</th>
                <th>Komponen</th>
                <th class="right">Tagihan</th>
                <th class="right">Dibayar</th>
                <th class="right">Sisa</th>
                <th class="center">Status</th>
                <th>Jatuh Tempo</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bills as $i => $b)
            <tr class="{{ $b->status }}">
                <td class="center">{{ $i + 1 }}</td>
                <td>{{ $b->student->name ?? '-' }}</td>
                <td class="center">{{ $b->period }}</td>
                <td>{{ $b->component }}</td>
                <td class="right">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($b->paid_amount, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($b->remaining(), 0, ',', '.') }}</td>
                <td class="center">{{ match($b->status) { 'paid' => 'Lunas', 'partial' => 'Sebagian', 'unpaid' => 'Belum Bayar', default => $b->status } }}</td>
                <td class="center">{{ $b->due_date?->format('Y-m-d') ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr class="summary">
                <td colspan="4" class="right">TOTAL</td>
                <td class="right">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</td>
                <td colspan="2" class="center">{{ $bills->count() }} tagihan</td>
            </tr>
        </tfoot>
    </table>

    <h3>Ringkasan</h3>
    <table>
        <tr class="summary">
            <td>Lunas</td>
            <td class="right">{{ $jumlahLunas }} tagihan</td>
        </tr>
        <tr>
            <td>Sebagian</td>
            <td class="right">{{ $jumlahSebagian }} tagihan</td>
        </tr>
        <tr>
            <td>Belum Bayar</td>
            <td class="right">{{ $jumlahBelumBayar }} tagihan</td>
        </tr>
    </table>

    <p style="font-size: 9px; color: #888; margin-top: 16px;">
        Dicetak dari SIMT MVP pada {{ now()->translatedFormat('d F Y H:i') }} WIB
    </p>
</body>
</html>
