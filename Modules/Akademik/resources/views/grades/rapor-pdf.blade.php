<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rapor Hasil Belajar - {{ $student->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            text-transform: uppercase;
        }
        .header p {
            margin: 3px 0 0;
            font-size: 10px;
            color: #666;
        }
        .info-table {
            width: 100%;
            margin-bottom: 20px;
            border-collapse: collapse;
        }
        .info-table td {
            padding: 4px 0;
            vertical-align: top;
        }
        .info-table td.label {
            width: 18%;
            font-weight: bold;
        }
        .info-table td.colon {
            width: 2%;
        }
        .info-table td.value {
            width: 30%;
        }
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }
        .data-table th, .data-table td {
            border: 1px solid #666;
            padding: 6px;
            text-align: left;
        }
        .data-table th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
        }
        .text-center {
            text-align: center;
        }
        .bold {
            font-weight: bold;
        }
        .attendance-box {
            width: 45%;
            border: 1px solid #666;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .attendance-box th, .attendance-box td {
            border: 1px solid #666;
            padding: 5px;
        }
        .attendance-box th {
            background-color: #f2f2f2;
            text-align: left;
        }
        .signature-table {
            width: 100%;
            margin-top: 40px;
        }
        .signature-table td {
            text-align: center;
            width: 33%;
        }
        .signature-space {
            height: 60px;
        }
    </style>
</head>
<body>

    <div class="header">
        <h2>Rapor Digital Hasil Belajar Siswa</h2>
        <h2>{{ auth()->user()->tenant->name ?? 'Madrasah Tsanawiyah' }}</h2>
        <p>Alamat: {{ auth()->user()->tenant->address ?? '-' }} | Telp: {{ auth()->user()->tenant->phone ?? '-' }}</p>
    </div>

    <table class="info-table">
        <tr>
            <td class="label">Nama Siswa</td>
            <td class="colon">:</td>
            <td class="value bold">{{ $student->name }}</td>
            <td class="label">Kelas / Rombel</td>
            <td class="colon">:</td>
            <td class="value">Kelas {{ $student->currentClass()->grade ?? '-' }}-{{ $student->currentClass()->name ?? '-' }}</td>
        </tr>
        <tr>
            <td class="label">NIS / NISN</td>
            <td class="colon">:</td>
            <td class="value">{{ $student->nis }} / {{ $student->nisn ?? '-' }}</td>
            <td class="label">Tahun Pelajaran</td>
            <td class="colon">:</td>
            <td class="value">{{ $student->currentClass()->schoolYear->name ?? '-' }}</td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th class="text-center" style="width: 5%;">No</th>
                <th style="width: 35%;">Mata Pelajaran</th>
                <th class="text-center" style="width: 10%;">Rata UH</th>
                <th class="text-center" style="width: 10%;">Rata Tugas</th>
                <th class="text-center" style="width: 8%;">UTS</th>
                <th class="text-center" style="width: 8%;">UAS</th>
                <th class="text-center" style="width: 12%; background-color: #e6f0ff;">Pengetahuan</th>
                <th class="text-center" style="width: 12%; background-color: #e6f9ff;">Keterampilan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($raporData as $index => $row)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>
                        <span class="bold">{{ $row['subject']->name }}</span><br>
                        <span style="font-size: 8px; color: #666; text-transform: uppercase;">{{ str_replace('_', ' ', $row['subject']->category) }}</span>
                    </td>
                    <td class="text-center">{{ $row['uh_average'] }}</td>
                    <td class="text-center">{{ $row['tugas_average'] }}</td>
                    <td class="text-center">{{ $row['uts'] }}</td>
                    <td class="text-center">{{ $row['uas'] }}</td>
                    <td class="text-center bold" style="background-color: #f2f7ff;">
                        {{ $row['pengetahuan'] }} ({{ $row['predicate_pengetahuan'] }})
                    </td>
                    <td class="text-center bold" style="background-color: #f2fdff;">
                        {{ $row['keterampilan'] }} ({{ $row['predicate_keterampilan'] }})
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center">Belum ada data nilai mata pelajaran untuk siswa ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="attendance-box">
        <thead>
            <tr>
                <th colspan="2">Ketidakhadiran (Bulan Ini)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Sakit (S)</td>
                <td class="text-center bold">{{ $attendanceSummary['sakit'] }} hari</td>
            </tr>
            <tr>
                <td>Izin (I)</td>
                <td class="text-center bold">{{ $attendanceSummary['izin'] }} hari</td>
            </tr>
            <tr>
                <td>Tanpa Keterangan (A)</td>
                <td class="text-center bold">{{ $attendanceSummary['alpha'] }} hari</td>
            </tr>
        </tbody>
    </table>

    <table class="signature-table">
        <tr>
            <td>
                Mengetahui,<br>
                Orang Tua / Wali Siswa
                <div class="signature-space"></div>
                (...........................................)
            </td>
            <td></td>
            <td>
                Malang, {{ now()->translatedFormat('d F Y') }}<br>
                Wali Kelas
                <div class="signature-space"></div>
                <span class="bold">( {{ $student->currentClass()->teacher->name ?? 'Guru Wali Kelas' }} )</span>
            </td>
        </tr>
    </table>

</body>
</html>
