<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Logbook {{ $nurse->name }} - {{ strtoupper(\Carbon\Carbon::create($year, $month)->translatedFormat('F Y')) }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; margin: 24px; color: #111; }
        h1, h2 { text-align: center; margin: 2px 0; font-size: 15px; }
        .sub { text-align: center; font-size: 12px; margin-bottom: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 2px 4px; text-align: center; }
        td.name, th.name { text-align: left; }
        .cat { background: #d1fadf; font-weight: bold; }
        .total { background: #f1f5f9; font-weight: bold; }
        .keterangan { margin-top: 18px; display: flex; justify-content: space-between; }
        .ttd { text-align: center; }
        .ttd span { display: block; margin-bottom: 48px; }
        @media print { body { margin: 12mm; } }
    </style>
</head>
<body>
    <h1>LOGBOOK PERAWAT</h1>
    <h2>RSU SAKINA IDAMAN</h2>
    <p class="sub">BULAN : {{ strtoupper(\Carbon\Carbon::create($year, $month)->translatedFormat('F Y')) }}</p>

    <table>
        <tr>
            <td class="name" style="width:12%" >NAMA</td>
            <td class="name" style="width:38%">: {{ $nurse->name }}</td>
            <td class="name" >PK</td>
            <td class="name" style="width:38%">: {{ $nurse->pk }}</td>
        </tr>
        <tr>
            <td class="name">UNIT</td>
            <td class="name">: {{ $nurse->unit->name }}</td>
            <td class="name">NIP</td>
            <td class="name">: {{ $nurse->nip }}</td>
        </tr>
    </table>

    <br>

    <table>
        <thead>
        <tr>
            <th style="width:26px">NO</th>
            <th class="name">INDIKATOR YANG DINILAI</th>
            <th>TARGET (%)</th>
            @for ($d = 1; $d <= $daysInMonth; $d++)
                <th style="width:18px">{{ $d }}</th>
            @endfor
            <th style="width:52px">CAPAIAN (%)</th>
        </tr>
        </thead>
        <tbody>
        @php $no = 1; @endphp
        @foreach ($gridCategories as $i => $gridRow)
            @php $catRow = $categories[$i] ?? null; @endphp
            <tr class="cat">
                <td colspan="{{ 3 + $daysInMonth }}">{{ $gridRow['category']->code }}. {{ $gridRow['category']->name }} ({{ $gridRow['category']->weight_percent }}%)</td>
                <td>0</td>
            </tr>
            @foreach ($gridRow['indicators'] as $gi)
                @php
                    $indicator = $gi['indicator'];
                    $cap = 0;
                    if ($catRow) {
                        foreach ($catRow['indicators'] as $capRow) {
                            if ($capRow['indicator']->id === $indicator->id) {
                                $cap = $capRow['achievement'];
                                break;
                            }
                        }
                    }
                @endphp
                <tr>
                    <td>{{ $no++ }}</td>
                    <td class="name">{{ $indicator->name }}</td>
                    <td>{{ $indicator->target }}</td>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        <td>{{ $gi['values'][$d] ?? '' }}</td>
                    @endfor
                    <td>{{ number_format($cap * 100, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="{{ 3 + $daysInMonth }}" class="name">JUMLAH RATA - RATA</td>
                <td>{{ $catRow ? number_format($catRow['achievement'] * 100, 2, ',', '.') : 0 }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="{{ 3 + $daysInMonth }}" class="name">TOTAL NILAI PERILAKU + PENGEMBANGAN PROFESI + KINERJA KLINIS</td>
            <td>{{ number_format($total * 100, 2, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>

    <p style="margin-top:6px"><strong>JUMLAH HARI :</strong> {{ $workingDays }}</p>

    <div class="keterangan">
        <div class="ttd">
            <span>KEPALA RUANG</span>
            <span>(.............................)</span>
        </div>
        <div class="ttd">
            <span>PEGAWAI YANG DIEVALUASI</span>
            <span>({{ $nurse->name }})</span>
        </div>
    </div>
</body>
</html>