<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Bulanan SPP - {{ $period }}</title>
    <style>
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #1e40af; padding-bottom: 8px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #1e40af; }
        .header p { margin: 2px 0; font-size: 10px; color: #666; }
        .title { font-size: 14px; font-weight: bold; text-align: center; margin-bottom: 15px; color: #111; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 6px 8px; text-align: left; }
        th { background: #1e40af; color: #fff; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .summary-row { background: #f3f4f6; font-weight: bold; }
        .right { text-align: right; }
        .center { text-align: center; }
        .paid { color: #16a34a; font-weight: bold; }
        .partial { color: #d97706; font-weight: bold; }
        .unpaid { color: #dc2626; font-weight: bold; }
        .stamp-section { width: 100%; margin-top: 30px; }
        .stamp-section td { border: 0; padding: 0; }
        .stamp-box { width: 200px; text-align: center; font-size: 11px; float: right; }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $tenant->name ?? 'SIMT MTs' }}</h1>
        <p>Sistem Informasi Manajemen Terpadu</p>
        <p>{{ $tenant->address ?? '-' }}</p>
    </div>

    <div class="title">
        Laporan Keuangan & Piutang SPP<br>
        Periode: {{ $period }}
    </div>

    <table>
        <thead>
            <tr>
                <th class="center" style="width: 25px;">No</th>
                <th>Siswa</th>
                <th>Komponen</th>
                <th class="right">Tagihan</th>
                <th class="right">Dibayar</th>
                <th class="right">Tunggakan</th>
                <th class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bills as $i => $b)
            <tr>
                <td class="center">{{ $i + 1 }}</td>
                <td><strong>{{ $b->student->name ?? '-' }}</strong></td>
                <td>{{ $b->component }}</td>
                <td class="right">Rp {{ number_format($b->amount, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($b->paid_amount, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($b->remaining(), 0, ',', '.') }}</td>
                <td class="center">
                    @if($b->status === 'paid')
                        <span class="paid">Lunas</span>
                    @elseif($b->status === 'partial')
                        <span class="partial">Sebagian</span>
                    @else
                        <span class="unpaid">Belum Bayar</span>
                    @endif
                </td>
            </tr>
            @endforeach
            <tr class="summary-row">
                <td colspan="3" class="right">TOTAL</td>
                <td class="right">Rp {{ number_format($totalTagihan, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalTunggakan, 0, ',', '.') }}</td>
                <td class="center">{{ $bills->count() }} siswa</td>
            </tr>
        </tbody>
    </table>

    <table class="stamp-section">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%;">
                <div class="stamp-box">
                    Mengetahui,<br>
                    Bendahara Sekolah<br><br><br><br>
                    <strong>_______________________</strong><br>
                    SIMT Finance Office
                </div>
            </td>
        </tr>
    </table>
</body>
</html>
