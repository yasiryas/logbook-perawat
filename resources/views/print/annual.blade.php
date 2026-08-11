<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Tahunan {{ $nurse->name }} - {{ $year }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 10px; margin: 24px; color: #111; }
        h1, h2 { text-align: center; margin: 2px 0; font-size: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 2px 4px; text-align: center; }
        td.name, th.name { text-align: left; }
        .cat { background: #dbe9e8; font-weight: bold; }
        .total { background: #f1f5f9; font-weight: bold; }
        .keterangan { margin-top: 18px; display: flex; justify-content: space-between; }
        .ttd { text-align: center; }
        .ttd span { display: block; margin-bottom: 48px; }
        @media print { body { margin: 12mm; } }
    </style>
</head>
<body>
    <h1>PENILAIAN KINERJA PERAWAT</h1>
    <h2>SESUAI STANDAR AKRIDITASI RUMAH SAKIT</h2>

    <table style="margin-top:10px">
        <tr>
            <td class="name">NAMA : {{ $nurse->name }}</td>
            <td class="name">PK : {{ $nurse->pk }}</td>
        </tr>
        <tr>
            <td class="name">UNIT : {{ $nurse->unit->name }}</td>
            <td class="name">TAHUN : {{ $year }}</td>
        </tr>
    </table>

    <br>

    @php
        $months = ['JANUARI','FEBRUARI','MARET','APRIL','MEI','JUNI','JULI','AGUSTUS','SEPTEMBER','OKTOBER','NOVEMBER','DESEMBER'];
    @endphp

    <table>
        <thead>
        <tr>
            <th style="width:26px">NO</th>
            <th class="name">INDIKATOR YANG DINILAI</th>
            @foreach ($months as $m)<th style="width:42px">{{ $m }}</th>@endforeach
            <th style="width:60px">RATA - RATA CAPAIAN</th>
        </tr>
        </thead>
        <tbody>
        @php $no = 1; @endphp
        @foreach ($categories as $catRows)
            @php $cat = $catRows[0]['category']; @endphp
            <tr class="cat">
                <td colspan="{{ 2 + count($months) + 1 }}" class="name">{{ $cat->code }}. {{ $cat->name }} ({{ $cat->weight_percent }}%)</td>
            </tr>
            @foreach ($catRows as $row)
                <tr>
                    <td>{{ $no++ }}</td>
                    <td class="name">{{ $row['indicator']->name }}</td>
                    @foreach ($row['monthly'] as $achievement)
                        <td>{{ number_format($achievement * 100, 2, ',', '.') }}</td>
                    @endforeach
                    <td>{{ number_format($row['achievement'] * 100, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            <tr class="total">
                <td colspan="{{ 2 + count($months) }}" class="name">JUMLAH RATA - RATA</td>
                <td>{{ number_format(array_sum(array_column($catRows, 'achievement')) / count($catRows) * 100, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total">
            <td colspan="{{ 2 + count($months) }}" class="name">TOTAL NILAI PERILAKU + PENGEMBANGAN PROFESI + KINERJA KLINIS</td>
            <td>{{ number_format($total * 100, 2, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>

    <div class="keterangan">
        <div class="ttd">
            <span>KEPALA RUANG</span>
            <span>(.............................)</span>
        </div>
        <div class="ttd">
            <span>PEGAWAI YANG DIEVALUASI</span>
            <span>({{ $nurse->name }})</span>
        </div>
        <div class="ttd">
            <span>MENGETAHUI KEPALA BIDANG KEPERAWATAN</span>
            <span>(.............................)</span>
        </div>
    </div>
</body>
</html>