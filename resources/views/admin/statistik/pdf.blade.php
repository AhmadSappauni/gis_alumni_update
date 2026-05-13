<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laporan Statistik Alumni</title>
    <style>
        @page { size: A4 portrait; margin: 15mm 12mm 15mm 12mm; }
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; line-height: 1.35; color: #0f172a; }
        .muted { color: #475569; }
        .title { font-size: 16px; font-weight: 800; letter-spacing: 0.4px; margin: 0 0 4px 0; text-transform: uppercase; }
        .subtitle { font-size: 12px; font-weight: 700; margin: 0; text-transform: uppercase; }
        .meta { margin-top: 8px; font-size: 10.5px; }
        .hr { height: 1px; background: #cbd5e1; margin: 10px 0 14px 0; }
        h2 { font-size: 12px; margin: 14px 0 8px 0; padding: 0; text-transform: uppercase; letter-spacing: 0.4px; }
        .section { margin-bottom: 12px; page-break-inside: auto; }
        .page-break { page-break-before: always; }
        .avoid-break { page-break-inside: avoid; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 7px; vertical-align: top; font-size: 10px; word-wrap: break-word; overflow-wrap: break-word; }
        th { background: #f1f5f9; font-weight: 800; text-align: left; }
        .nowrap { white-space: nowrap; }
        .right { text-align: right; }
        .center { text-align: center; }
        .note { margin-top: 6px; font-size: 10px; color: #475569; }
        .two-col { width: 100%; }
        .insight { margin: 0; padding-left: 16px; }
        .insight li { margin: 0 0 4px 0; }

    </style>
</head>
<body>
@php
    $payload = $payload ?? [];
    $k = (array)($payload['kpis'] ?? []);
    $c = (array)($payload['charts'] ?? []);
    $showUnknown = (bool)($showUnknown ?? false);

    $fmtNum = fn($n) => number_format((float)($n ?? 0), 0, ',', '.');
    $fmtDec = fn($n, $d=1) => is_numeric($n) ? number_format((float)$n, $d, ',', '.') : '-';

    $isUnknown = function ($label) {
        if ($label === null) return true;
        $s = trim((string)$label);
        if ($s === '' || $s === '-') return true;
        $lower = strtolower(preg_replace('/\s+/', ' ', $s) ?? $s);
        $lower = str_replace(['–','—'], '-', $lower);
        if (in_array($lower, ['null','n/a','na','none','unknown'], true)) return true;
        if (str_contains($lower, 'tidak diketahui')) return true;
        if (str_contains($lower, 'belum diketahui')) return true;
        if (str_contains($lower, 'belum diisi')) return true;
        if (str_contains($lower, 'kosong')) return true;
        return false;
    };

    $filterUnknown = function ($labels, $data) use ($showUnknown, $isUnknown) {
        $labels = is_array($labels) ? $labels : [];
        $data = is_array($data) ? $data : [];
        $len = min(count($labels), count($data));
        $labels = array_slice($labels, 0, $len);
        $data = array_slice($data, 0, $len);
        if ($showUnknown) return ['labels' => $labels, 'data' => $data];
        $outL = []; $outD = [];
        for ($i=0; $i<$len; $i++) {
            if ($isUnknown($labels[$i])) continue;
            $outL[] = $labels[$i];
            $outD[] = $data[$i];
        }
        return ['labels' => $outL, 'data' => $outD];
    };

    $sumData = function ($data) { $t=0; foreach((array)$data as $v){ $t += (int)$v; } return $t; };
@endphp

<div class="section">
    <div class="title">Laporan Statistik Alumni</div>
    <div class="subtitle">Jurusan Pendidikan Komputer</div>
    <div class="subtitle">Fakultas Keguruan dan Ilmu Pendidikan</div>
    <div class="subtitle">Universitas Lambung Mangkurat</div>
    <div class="meta muted">
        <div>Tanggal Cetak: <span class="nowrap">{{ ($printedAt ?? now())->format('d-m-Y H:i') }}</span></div>
    </div>
    <div class="hr"></div>
</div>

<div class="section">
    <h2>Informasi Filter</h2>
    <table>
        <thead>
        <tr>
            <th style="width: 38%;">Filter</th>
            <th>Nilai</th>
        </tr>
        </thead>
        <tbody>
        @foreach(($filterRows ?? []) as $row)
            <tr>
                <td>{{ $row['Filter'] ?? '' }}</td>
                <td>{{ $row['Nilai'] ?? '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Ringkasan Umum</h2>
    @php
        $sd = (array)($c['salary_distribution'] ?? []);
        $salaryValid = (int)($sd['total_valid'] ?? 0);
        $salaryUnknown = (int)($sd['total_unknown'] ?? 0);
    @endphp
    <table>
        <thead>
        <tr>
            <th>Indikator</th>
            <th class="right" style="width: 28%;">Nilai</th>
        </tr>
        </thead>
        <tbody>
        <tr><td>Total Alumni</td><td class="right">{{ $fmtNum($k['total_alumni'] ?? 0) }}</td></tr>
        <tr><td>Alumni Bekerja</td><td class="right">{{ $fmtNum($k['bekerja'] ?? 0) }}</td></tr>
        <tr><td>Belum Bekerja</td><td class="right">{{ $fmtNum($k['belum_bekerja'] ?? 0) }}</td></tr>
        <tr><td>Studi Lanjut</td><td class="right">{{ $fmtNum($k['studi_lanjut'] ?? 0) }}</td></tr>
        <tr><td>Multi-job</td><td class="right">{{ $fmtNum($k['multi_job'] ?? 0) }}</td></tr>
        <tr><td>Rata-rata Masa Tunggu</td><td class="right">{{ is_numeric($k['rata_masa_tunggu'] ?? null) ? ($fmtDec($k['rata_masa_tunggu'], 1).' bulan') : '-' }}</td></tr>
        <tr><td>Rata-rata TOEFL</td><td class="right">{{ is_numeric($k['rata_toefl'] ?? null) ? $fmtNum(round((float)$k['rata_toefl'])) : '-' }}</td></tr>
        <tr><td>Data Gaji Valid</td><td class="right">{{ $fmtNum($salaryValid) }}</td></tr>
        <tr><td>Data Gaji Tidak Diketahui</td><td class="right">{{ $fmtNum($salaryUnknown) }}</td></tr>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Insight Utama</h2>
    @if(!empty($insights))
        <ul class="insight">
            @foreach($insights as $t)
                <li>{{ $t }}</li>
            @endforeach
        </ul>
    @else
        <div class="muted">Insight belum tersedia karena data tidak mencukupi.</div>
    @endif
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Statistik Ketenagakerjaan</h2>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Status Alumni</div>
        @php
            $total = (int)($k['total_alumni'] ?? 0);
            $rows = [
                ['Bekerja', (int)($k['bekerja'] ?? 0)],
                ['Belum Bekerja', (int)($k['belum_bekerja'] ?? 0)],
                ['Studi Lanjut', (int)($k['studi_lanjut'] ?? 0)],
            ];
        @endphp
        <table>
            <thead><tr><th>Status</th><th class="right" style="width:20%;">Jumlah</th><th class="right" style="width:20%;">Persentase</th></tr></thead>
            <tbody>
            @foreach($rows as [$label,$val])
                <tr>
                    <td>{{ $label }}</td>
                    <td class="right">{{ $fmtNum($val) }}</td>
                    <td class="right">{{ $total > 0 ? (round(($val/$total)*100).'%') : '-' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Masa Tunggu Kerja</div>
        @php $mt = $filterUnknown($c['masa_tunggu']['labels'] ?? [], $c['masa_tunggu']['data'] ?? []); @endphp
        <table>
            <thead><tr><th>Rentang</th><th class="right" style="width:20%;">Jumlah</th></tr></thead>
            <tbody>
            @foreach($mt['labels'] as $i => $lab)
                <tr><td>{{ $lab }}</td><td class="right">{{ $fmtNum($mt['data'][$i] ?? 0) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        @if(!$showUnknown)
            @php
                $allMt = $filterUnknown($c['masa_tunggu']['labels'] ?? [], $c['masa_tunggu']['data'] ?? []);
                $unknownCount = 0;
                $labelsAll = (array)($c['masa_tunggu']['labels'] ?? []);
                $dataAll = (array)($c['masa_tunggu']['data'] ?? []);
                foreach($labelsAll as $idx => $lab){ if($isUnknown($lab)) $unknownCount += (int)($dataAll[$idx] ?? 0); }
            @endphp
            @if($unknownCount>0)<div class="note">Tidak diketahui (disembunyikan): {{ $fmtNum($unknownCount) }} alumni</div>@endif
        @endif
    </div>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Top 10 Perusahaan/Instansi Tujuan</div>
        @php $tc = $filterUnknown($c['top_company']['labels'] ?? [], $c['top_company']['data'] ?? []); @endphp
        <table>
            <thead><tr><th class="center" style="width:7%;">No</th><th>Instansi/Perusahaan</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
            <tbody>
            @foreach($tc['labels'] as $i => $lab)
                <tr><td class="center">{{ $i+1 }}</td><td>{{ $lab }}</td><td class="right">{{ $fmtNum($tc['data'][$i] ?? 0) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:0;">
        <div style="font-weight:800; margin-bottom:6px;">Top 5 Bidang Pekerjaan</div>
        @php $tb = $filterUnknown($c['top_bidang']['labels'] ?? [], $c['top_bidang']['data'] ?? []); @endphp
        <table>
            <thead><tr><th class="center" style="width:7%;">No</th><th>Bidang</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
            <tbody>
            @foreach($tb['labels'] as $i => $lab)
                <tr><td class="center">{{ $i+1 }}</td><td>{{ $lab }}</td><td class="right">{{ $fmtNum($tb['data'][$i] ?? 0) }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Profil & Relevansi Alumni</h2>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Jenis Kelamin</div>
        @php
            $g = $filterUnknown($c['gender']['labels'] ?? [], $c['gender']['data'] ?? []);
            $gTotal = $sumData($g['data']);
        @endphp
        <table>
            <thead><tr><th>Jenis Kelamin</th><th class="right" style="width:20%;">Jumlah</th><th class="right" style="width:20%;">Persentase</th></tr></thead>
            <tbody>
            @foreach($g['labels'] as $i => $lab)
                @php $val = (int)($g['data'][$i] ?? 0); @endphp
                <tr><td>{{ $lab }}</td><td class="right">{{ $fmtNum($val) }}</td><td class="right">{{ $gTotal>0 ? (round(($val/$gTotal)*100).'%') : '-' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Distribusi Nilai TOEFL</div>
        @php
            $toeflLabels = (array)($c['toefl_dist']['labels'] ?? []);
            $toeflData = (array)($c['toefl_dist']['data'] ?? []);
            $to = $filterUnknown($toeflLabels, $toeflData);
        @endphp
        <table>
            <thead><tr><th>Kelompok Nilai</th><th class="right" style="width:20%;">Jumlah</th></tr></thead>
            <tbody>
            @foreach($to['labels'] as $i => $lab)
                <tr><td>{{ $lab }}</td><td class="right">{{ $fmtNum($to['data'][$i] ?? 0) }}</td></tr>
            @endforeach
            </tbody>
        </table>
        @php
            $toeflValid = (int)($c['toefl_dist']['valid_count'] ?? ($k['toefl_valid_count'] ?? 0));
            $toeflUnknown = 0;
            foreach($toeflLabels as $i => $lab){ if($isUnknown($lab)) $toeflUnknown += (int)($toeflData[$i] ?? 0); }
        @endphp
        <div class="note">Data TOEFL valid: {{ $fmtNum($toeflValid) }} alumni @if($toeflUnknown>0) • Tidak diketahui: {{ $fmtNum($toeflUnknown) }} alumni @endif</div>
    </div>

    <div style="margin-bottom:0;">
        <div style="font-weight:800; margin-bottom:6px;">Kesesuaian Bidang Ilmu</div>
        @php
            $lin = $filterUnknown($c['linearitas']['labels'] ?? [], $c['linearitas']['data'] ?? []);
            $linTotal = $sumData($lin['data']);
        @endphp
        <table>
            <thead><tr><th>Kategori</th><th class="right" style="width:20%;">Jumlah</th><th class="right" style="width:20%;">Persentase</th></tr></thead>
            <tbody>
            @foreach($lin['labels'] as $i => $lab)
                @php $val=(int)($lin['data'][$i] ?? 0); @endphp
                <tr><td>{{ $lab }}</td><td class="right">{{ $fmtNum($val) }}</td><td class="right">{{ $linTotal>0 ? (round(($val/$linTotal)*100).'%') : '-' }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Persebaran Wilayah Kerja</h2>
    @php $tw = $filterUnknown($c['top_wilayah']['labels'] ?? [], $c['top_wilayah']['data'] ?? []); @endphp
    <table>
        <thead><tr><th class="center" style="width:7%;">No</th><th>Wilayah</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
        <tbody>
        @foreach($tw['labels'] as $i => $lab)
            <tr><td class="center">{{ $i+1 }}</td><td>{{ $lab }}</td><td class="right">{{ $fmtNum($tw['data'][$i] ?? 0) }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Studi Lanjut</h2>
    @php $tk = $filterUnknown($c['top_kampus']['labels'] ?? [], $c['top_kampus']['data'] ?? []); @endphp
    <table>
        <thead><tr><th class="center" style="width:7%;">No</th><th>Nama Kampus</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
        <tbody>
        @foreach($tk['labels'] as $i => $lab)
            <tr><td class="center">{{ $i+1 }}</td><td>{{ $lab }}</td><td class="right">{{ $fmtNum($tk['data'][$i] ?? 0) }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Statistik Gaji Alumni</h2>
    <div style="font-weight:800; margin-bottom:6px;">Distribusi Rentang Gaji Alumni</div>
    @php
        $sdl = (array)($c['salary_distribution']['labels'] ?? []);
        $sdd = (array)($c['salary_distribution']['data'] ?? []);
        $sdFiltered = $filterUnknown($sdl, $sdd);
        $unknownSalary = (int)($c['salary_distribution']['total_unknown'] ?? 0);
    @endphp
    <table>
        <thead><tr><th>Rentang Gaji</th><th class="right" style="width:20%;">Jumlah</th></tr></thead>
        <tbody>
        @foreach($sdFiltered['labels'] as $i => $lab)
            <tr><td>{{ $lab }}</td><td class="right">{{ $fmtNum($sdFiltered['data'][$i] ?? 0) }}</td></tr>
        @endforeach
        </tbody>
    </table>
    @if($unknownSalary > 0)
        <div class="note">Data gaji tidak diketahui: {{ $fmtNum($unknownSalary) }} alumni</div>
    @endif
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Tren Alumni</h2>
    @php
        $trendLabels = (array)($c['tren_angkatan']['labels'] ?? []);
        $trendTotal = (array)($c['tren_angkatan']['total'] ?? []);
        $trendBekerja = (array)($c['tren_angkatan']['bekerja'] ?? []);
        $trendBelum = (array)($c['tren_angkatan']['belum_bekerja'] ?? []);
        $len = min(count($trendLabels), count($trendTotal));
    @endphp

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Tren Alumni per Angkatan</div>
        <table>
            <thead><tr><th style="width:22%;">Angkatan</th><th class="right">Jumlah Alumni</th></tr></thead>
            <tbody>
            @for($i=0; $i<$len; $i++)
                <tr><td>{{ $trendLabels[$i] }}</td><td class="right">{{ $fmtNum($trendTotal[$i] ?? 0) }}</td></tr>
            @endfor
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:0;">
        <div style="font-weight:800; margin-bottom:6px;">Tren Keterserapan Kerja per Angkatan</div>
        <table>
            <thead><tr><th style="width:22%;">Angkatan</th><th class="right">Bekerja</th><th class="right">Belum Bekerja</th></tr></thead>
            <tbody>
            @for($i=0; $i<$len; $i++)
                <tr>
                    <td>{{ $trendLabels[$i] }}</td>
                    <td class="right">{{ $fmtNum($trendBekerja[$i] ?? 0) }}</td>
                    <td class="right">{{ $fmtNum($trendBelum[$i] ?? 0) }}</td>
                </tr>
            @endfor
            </tbody>
        </table>
    </div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Kualitas Data</h2>
    @php
        $totalAlumni = (int)($k['total_alumni'] ?? 0);
        $genderLabelsAll = (array)($c['gender']['labels'] ?? []);
        $genderDataAll = (array)($c['gender']['data'] ?? []);
        $genderUnknown = 0;
        foreach($genderLabelsAll as $i => $lab){ if($isUnknown($lab)) $genderUnknown += (int)($genderDataAll[$i] ?? 0); }
        $genderValid = max(0, $sumData($genderDataAll) - $genderUnknown);

        $toeflLabelsAll = (array)($c['toefl_dist']['labels'] ?? []);
        $toeflDataAll = (array)($c['toefl_dist']['data'] ?? []);
        $toeflUnknown = 0;
        foreach($toeflLabelsAll as $i => $lab){ if($isUnknown($lab)) $toeflUnknown += (int)($toeflDataAll[$i] ?? 0); }
        $toeflValid = max(0, $sumData($toeflDataAll) - $toeflUnknown);

        $salaryValid = (int)($c['salary_distribution']['total_valid'] ?? 0);
        $salaryUnknown = (int)($c['salary_distribution']['total_unknown'] ?? 0);

        $bidangLabelsAll = (array)($c['top_bidang']['labels'] ?? []);
        $bidangDataAll = (array)($c['top_bidang']['data'] ?? []);
        $bidangUnknown = 0;
        foreach($bidangLabelsAll as $i => $lab){ if($isUnknown($lab)) $bidangUnknown += (int)($bidangDataAll[$i] ?? 0); }
        $bidangValid = max(0, $sumData($bidangDataAll) - $bidangUnknown);

        $wilLabelsAll = (array)($c['top_wilayah']['labels'] ?? []);
        $wilDataAll = (array)($c['top_wilayah']['data'] ?? []);
        $wilUnknown = 0;
        foreach($wilLabelsAll as $i => $lab){ if($isUnknown($lab)) $wilUnknown += (int)($wilDataAll[$i] ?? 0); }
        $wilValid = max(0, $sumData($wilDataAll) - $wilUnknown);
    @endphp
    <table>
        <thead><tr><th>Komponen Data</th><th class="right" style="width:20%;">Data Valid</th><th class="right" style="width:20%;">Tidak Diketahui</th></tr></thead>
        <tbody>
        <tr><td>Jenis Kelamin</td><td class="right">{{ $fmtNum($genderValid) }}</td><td class="right">{{ $fmtNum($genderUnknown) }}</td></tr>
        <tr><td>TOEFL</td><td class="right">{{ $fmtNum($toeflValid) }}</td><td class="right">{{ $fmtNum($toeflUnknown) }}</td></tr>
        <tr><td>Gaji</td><td class="right">{{ $fmtNum($salaryValid) }}</td><td class="right">{{ $fmtNum($salaryUnknown) }}</td></tr>
        <tr><td>Bidang Pekerjaan</td><td class="right">{{ $fmtNum($bidangValid) }}</td><td class="right">{{ $fmtNum($bidangUnknown) }}</td></tr>
        <tr><td>Wilayah Kerja</td><td class="right">{{ $fmtNum($wilValid) }}</td><td class="right">{{ $fmtNum($wilUnknown) }}</td></tr>
        </tbody>
    </table>
    <div class="note">Catatan: komponen “Bidang Pekerjaan” dan “Wilayah Kerja” dihitung dari data pekerjaan aktif/utama pada filter saat ini.</div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Kesimpulan & Rekomendasi</h2>
    @php
        $topPick = function($labels,$data) use ($filterUnknown) {
            $f = $filterUnknown($labels,$data);
            $bestIdx = null; $bestVal = -INF;
            foreach((array)$f['data'] as $i => $v) {
                $n = is_numeric($v) ? (float)$v : 0.0;
                if ($n > $bestVal) { $bestVal = $n; $bestIdx = $i; }
            }
            if ($bestIdx === null || $bestVal <= 0) return null;
            return (string)($f['labels'][$bestIdx] ?? '');
        };
        $topBidang = $topPick($c['top_bidang']['labels'] ?? [], $c['top_bidang']['data'] ?? []);
        $topWil = $topPick($c['top_wilayah']['labels'] ?? [], $c['top_wilayah']['data'] ?? []);
    @endphp
    <div style="font-weight:800; margin-bottom:6px;">Kesimpulan</div>
    <ol style="margin:0; padding-left:18px;">
        <li>Jumlah alumni terdata sebanyak {{ $fmtNum($k['total_alumni'] ?? 0) }} orang.</li>
        <li>Alumni bekerja sebanyak {{ $fmtNum($k['bekerja'] ?? 0) }} orang.</li>
        @if(!empty($topBidang))<li>Bidang pekerjaan terbanyak adalah {{ $topBidang }}.</li>@endif
        @if(!empty($topWil))<li>Wilayah kerja terbanyak adalah {{ $topWil }}.</li>@endif
        @if(($salaryUnknown ?? 0) > 0)<li>Data yang masih perlu dilengkapi: gaji (tidak diketahui {{ $fmtNum($salaryUnknown) }} alumni).</li>@endif
    </ol>

    <div style="font-weight:800; margin:10px 0 6px 0;">Rekomendasi</div>
    <ol style="margin:0; padding-left:18px;">
        <li>Melengkapi data alumni yang masih tidak diketahui (khususnya gaji, TOEFL, dan atribut pekerjaan).</li>
        <li>Memperbarui data pekerjaan alumni secara berkala agar statistik lebih akurat.</li>
        <li>Menggunakan laporan statistik ini sebagai bahan evaluasi kurikulum dan tracer study.</li>
    </ol>
</div>

<div class="footer">
    <div class="line">
        Laporan Statistik Alumni • Dicetak {{ ($printedAt ?? now())->format('d-m-Y H:i') }}
    </div>
</div>

</body>
</html>
