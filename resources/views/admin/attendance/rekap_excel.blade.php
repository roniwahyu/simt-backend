<table>
    <thead>
        <tr>
            <th colspan="{{ $daysInMonth + 6 }}" style="font-weight: bold; font-size: 14pt; text-align: center;">
                REKAP PRESENSI BULANAN - {{ strtoupper($start->translatedFormat('F Y')) }}
            </th>
        </tr>
        <tr>
            <th colspan="{{ $daysInMonth + 6 }}" style="font-weight: bold; font-size: 11pt; text-align: center;">
                Kelas: {{ $class->name }} — Tahun Ajaran: {{ $class->schoolYear->name ?? '-' }}
            </th>
        </tr>
        <tr></tr> <!-- Empty row -->
        <tr>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #f2f2f2; text-align: left;">Nama Siswa</th>
            @for($d = 1; $d <= $daysInMonth; $d++)
                <th style="font-weight: bold; border: 1px solid #000000; background-color: #f2f2f2; text-align: center; width: 5px;">{{ $d }}</th>
            @endfor
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #d4edda; text-align: center; width: 6px;">H</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #f8d7da; text-align: center; width: 6px;">A</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #fff3cd; text-align: center; width: 6px;">I</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #fff3cd; text-align: center; width: 6px;">S</th>
            <th style="font-weight: bold; border: 1px solid #000000; background-color: #cce5ff; text-align: center; width: 6px;">T</th>
        </tr>
    </thead>
    <tbody>
        @foreach($students as $s)
            @php $counts = ['H'=>0,'A'=>0,'I'=>0,'S'=>0,'T'=>0]; @endphp
            <tr>
                <td style="border: 1px solid #000000; text-align: left;">{{ $s->name }}</td>
                @for($d = 1; $d <= $daysInMonth; $d++)
                    @php
                        $dateKey = $start->copy()->day($d)->format('Y-m-d');
                        $rec = $s->monthly[$dateKey] ?? null;
                        $st = $rec->status ?? null;
                        if ($st) $counts[$st]++;
                    @endphp
                    <td style="border: 1px solid #000000; text-align: center;">{{ $st ?? '·' }}</td>
                @endfor
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: #155724; background-color: #e2f0d9;">{{ $counts['H'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: #721c24; background-color: #f8cbad;">{{ $counts['A'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: #856404; background-color: #fff2cc;">{{ $counts['I'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: #856404; background-color: #fff2cc;">{{ $counts['S'] }}</td>
                <td style="border: 1px solid #000000; text-align: center; font-weight: bold; color: #004085; background-color: #b4c6e7;">{{ $counts['T'] }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
