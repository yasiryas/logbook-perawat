<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Penilaian Tahunan {{ $assessment->nurse->name }} - {{ $assessment->year }}</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Times New Roman', serif; font-size: 11px; margin: 24px; color: #111; }
        h1, h2, h3 { text-align: center; margin: 2px 0; font-size: 14px; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #333; padding: 3px 5px; text-align: center; }
        td.name, th.name { text-align: left; }
        .cat { background: #dbe9e8; font-weight: bold; }
        .total { background: #f1f5f9; font-weight: bold; }
        .ttd { text-align: center; }
        .ttd span { display: block; margin-bottom: 48px; }
        .ttd-row { margin-top: 18px; display: flex; justify-content: space-between; }
        @media print { body { margin: 12mm; } }
    </style>
</head>
<body>
    <h1>PENILAIAN KINERJA PERAWAT</h1>
    <h2>SESUAI STANDAR AKRIDITASI RUMAH SAKIT</h2>
    <h3>PROFESI PERAWAT</h3>

    <table style="margin-top:10px">
        <tr>
            <td class="name" style="width:50%">NAMA PEGAWAI YANG DINILAI : {{ $assessment->nurse->name }}</td>
            <td class="name">NIP : {{ $assessment->nurse->nip }}</td>
        </tr>
        <tr>
            <td class="name">JABATAN : PERAWAT</td>
            <td class="name">PK : {{ $assessment->nurse->pk }}</td>
        </tr>
        <tr>
            <td class="name" colspan="2">UNIT KERJA : {{ $assessment->nurse->unit->name }}</td>
        </tr>
        <tr>
            <td class="name" colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td class="name" colspan="2"><strong>PENILAI</strong></td>
        </tr>
        <tr>
            <td class="name">NAMA : {{ $assessment->assessor_name }}</td>
            <td class="name">NIP : {{ $assessment->assessor_nip }}</td>
        </tr>
        <tr>
            <td class="name">JABATAN : {{ $assessment->assessor_position }}</td>
            <td class="name">UNIT : {{ $assessment->assessor_unit }}</td>
        </tr>
        <tr>
            <td class="name" colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td class="name" colspan="2"><strong>ATASAN PEJABAT PENILAI</strong></td>
        </tr>
        <tr>
            <td class="name">NAMA : {{ $assessment->superior_name }}</td>
            <td class="name">NIP : {{ $assessment->superior_nip }}</td>
        </tr>
        <tr>
            <td class="name">JABATAN : {{ $assessment->superior_position }}</td>
            <td class="name">UNIT : {{ $assessment->superior_unit }}</td>
        </tr>
    </table>

    <br>

    <table>
        <thead>
        <tr>
            <th style="width:26px">NO</th>
            <th class="name">INDIKATOR YANG DINILAI</th>
            <th style="width:60px">TARGET</th>
            <th style="width:80px">CAPAIAN</th>
            <th style="width:60px">BOBOT (%)</th>
            <th style="width:90px">NILAI HASIL KINERJA</th>
        </tr>
        </thead>
        <tbody>
        @php $no = 1; $totalScore = 0; @endphp
        @foreach ($assessment->items->groupBy('indicator.category_id') as $group)
            @php $cat = $group->first()->indicator->category; @endphp
            <tr class="cat">
                <td colspan="6" class="name">{{ $cat->code }}. {{ $cat->name }} ({{ $cat->weight_percent }}%)</td>
            </tr>
            @foreach ($group as $item)
                @php $totalScore += $item->score; @endphp
                <tr>
                    <td>{{ $no++ }}</td>
                    <td class="name">{{ $item->indicator->name }}</td>
                    <td>{{ $item->target }}</td>
                    <td>{{ number_format($item->achievement, 6, ',', '.') }}</td>
                    <td>{{ $item->weight }}</td>
                    <td>{{ number_format($item->score, 6, ',', '.') }}</td>
                </tr>
            @endforeach
        @endforeach
        <tr class="total">
            <td colspan="3" class="name">TOTAL</td>
            <td colspan="3">{{ number_format($totalScore, 6, ',', '.') }}</td>
        </tr>
        </tbody>
    </table>

    <div class="ttd-row">
        <div class="ttd">
            <span>KEPALA RUANG</span>
            <span>(.............................)</span>
        </div>
        <div class="ttd">
            <span>PEGAWAI YANG DIEVALUASI</span>
            <span>({{ $assessment->nurse->name }})</span>
        </div>
        <div class="ttd">
            <span>MENGETAHUI</span>
            <span>KEPALA BIDANG KEPERAWATAN</span>
            <span>(.............................)</span>
        </div>
    </div>
</body>
</html>