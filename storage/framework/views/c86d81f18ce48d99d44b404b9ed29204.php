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
<?php
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
?>

<div class="section">
    <div class="title">Laporan Statistik Alumni</div>
    <div class="subtitle">Jurusan Pendidikan Komputer</div>
    <div class="subtitle">Fakultas Keguruan dan Ilmu Pendidikan</div>
    <div class="subtitle">Universitas Lambung Mangkurat</div>
    <div class="meta muted">
        <div>Tanggal Cetak: <span class="nowrap"><?php echo e(($printedAt ?? now())->format('d-m-Y H:i')); ?></span></div>
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
        <?php $__currentLoopData = ($filterRows ?? []); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $row): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr>
                <td><?php echo e($row['Filter'] ?? ''); ?></td>
                <td><?php echo e($row['Nilai'] ?? ''); ?></td>
            </tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Ringkasan Umum</h2>
    <?php
        $sd = (array)($c['salary_distribution'] ?? []);
        $salaryValid = (int)($sd['total_valid'] ?? 0);
        $salaryUnknown = (int)($sd['total_unknown'] ?? 0);
    ?>
    <table>
        <thead>
        <tr>
            <th>Indikator</th>
            <th class="right" style="width: 28%;">Nilai</th>
        </tr>
        </thead>
        <tbody>
        <tr><td>Total Alumni</td><td class="right"><?php echo e($fmtNum($k['total_alumni'] ?? 0)); ?></td></tr>
        <tr><td>Alumni Bekerja</td><td class="right"><?php echo e($fmtNum($k['bekerja'] ?? 0)); ?></td></tr>
        <tr><td>Belum Bekerja</td><td class="right"><?php echo e($fmtNum($k['belum_bekerja'] ?? 0)); ?></td></tr>
        <tr><td>Studi Lanjut</td><td class="right"><?php echo e($fmtNum($k['studi_lanjut'] ?? 0)); ?></td></tr>
        <tr><td>Multi-job</td><td class="right"><?php echo e($fmtNum($k['multi_job'] ?? 0)); ?></td></tr>
        <tr><td>Rata-rata Masa Tunggu</td><td class="right"><?php echo e(is_numeric($k['rata_masa_tunggu'] ?? null) ? ($fmtDec($k['rata_masa_tunggu'], 1).' bulan') : '-'); ?></td></tr>
        <tr><td>Rata-rata TOEFL</td><td class="right"><?php echo e(is_numeric($k['rata_toefl'] ?? null) ? $fmtNum(round((float)$k['rata_toefl'])) : '-'); ?></td></tr>
        <tr><td>Data Gaji Valid</td><td class="right"><?php echo e($fmtNum($salaryValid)); ?></td></tr>
        <tr><td>Data Gaji Tidak Diketahui</td><td class="right"><?php echo e($fmtNum($salaryUnknown)); ?></td></tr>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Insight Utama</h2>
    <?php if(!empty($insights)): ?>
        <ul class="insight">
            <?php $__currentLoopData = $insights; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $t): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($t); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    <?php else: ?>
        <div class="muted">Insight belum tersedia karena data tidak mencukupi.</div>
    <?php endif; ?>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Statistik Ketenagakerjaan</h2>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Status Alumni</div>
        <?php
            $total = (int)($k['total_alumni'] ?? 0);
            $rows = [
                ['Bekerja', (int)($k['bekerja'] ?? 0)],
                ['Belum Bekerja', (int)($k['belum_bekerja'] ?? 0)],
                ['Studi Lanjut', (int)($k['studi_lanjut'] ?? 0)],
            ];
        ?>
        <table>
            <thead><tr><th>Status</th><th class="right" style="width:20%;">Jumlah</th><th class="right" style="width:20%;">Persentase</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $rows; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as [$label,$val]): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr>
                    <td><?php echo e($label); ?></td>
                    <td class="right"><?php echo e($fmtNum($val)); ?></td>
                    <td class="right"><?php echo e($total > 0 ? (round(($val/$total)*100).'%') : '-'); ?></td>
                </tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Masa Tunggu Kerja</div>
        <?php $mt = $filterUnknown($c['masa_tunggu']['labels'] ?? [], $c['masa_tunggu']['data'] ?? []); ?>
        <table>
            <thead><tr><th>Rentang</th><th class="right" style="width:20%;">Jumlah</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $mt['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($mt['data'][$i] ?? 0)); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <?php if(!$showUnknown): ?>
            <?php
                $allMt = $filterUnknown($c['masa_tunggu']['labels'] ?? [], $c['masa_tunggu']['data'] ?? []);
                $unknownCount = 0;
                $labelsAll = (array)($c['masa_tunggu']['labels'] ?? []);
                $dataAll = (array)($c['masa_tunggu']['data'] ?? []);
                foreach($labelsAll as $idx => $lab){ if($isUnknown($lab)) $unknownCount += (int)($dataAll[$idx] ?? 0); }
            ?>
            <?php if($unknownCount>0): ?><div class="note">Tidak diketahui (disembunyikan): <?php echo e($fmtNum($unknownCount)); ?> alumni</div><?php endif; ?>
        <?php endif; ?>
    </div>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Top 10 Perusahaan/Instansi Tujuan</div>
        <?php $tc = $filterUnknown($c['top_company']['labels'] ?? [], $c['top_company']['data'] ?? []); ?>
        <table>
            <thead><tr><th class="center" style="width:7%;">No</th><th>Instansi/Perusahaan</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $tc['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr><td class="center"><?php echo e($i+1); ?></td><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($tc['data'][$i] ?? 0)); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:0;">
        <div style="font-weight:800; margin-bottom:6px;">Top 5 Bidang Pekerjaan</div>
        <?php $tb = $filterUnknown($c['top_bidang']['labels'] ?? [], $c['top_bidang']['data'] ?? []); ?>
        <table>
            <thead><tr><th class="center" style="width:7%;">No</th><th>Bidang</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $tb['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr><td class="center"><?php echo e($i+1); ?></td><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($tb['data'][$i] ?? 0)); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Profil & Relevansi Alumni</h2>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Jenis Kelamin</div>
        <?php
            $g = $filterUnknown($c['gender']['labels'] ?? [], $c['gender']['data'] ?? []);
            $gTotal = $sumData($g['data']);
        ?>
        <table>
            <thead><tr><th>Jenis Kelamin</th><th class="right" style="width:20%;">Jumlah</th><th class="right" style="width:20%;">Persentase</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $g['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $val = (int)($g['data'][$i] ?? 0); ?>
                <tr><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($val)); ?></td><td class="right"><?php echo e($gTotal>0 ? (round(($val/$gTotal)*100).'%') : '-'); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Distribusi Nilai TOEFL</div>
        <?php
            $toeflLabels = (array)($c['toefl_dist']['labels'] ?? []);
            $toeflData = (array)($c['toefl_dist']['data'] ?? []);
            $to = $filterUnknown($toeflLabels, $toeflData);
        ?>
        <table>
            <thead><tr><th>Kelompok Nilai</th><th class="right" style="width:20%;">Jumlah</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $to['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <tr><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($to['data'][$i] ?? 0)); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
        <?php
            $toeflValid = (int)($c['toefl_dist']['valid_count'] ?? ($k['toefl_valid_count'] ?? 0));
            $toeflUnknown = 0;
            foreach($toeflLabels as $i => $lab){ if($isUnknown($lab)) $toeflUnknown += (int)($toeflData[$i] ?? 0); }
        ?>
        <div class="note">Data TOEFL valid: <?php echo e($fmtNum($toeflValid)); ?> alumni <?php if($toeflUnknown>0): ?> • Tidak diketahui: <?php echo e($fmtNum($toeflUnknown)); ?> alumni <?php endif; ?></div>
    </div>

    <div style="margin-bottom:0;">
        <div style="font-weight:800; margin-bottom:6px;">Kesesuaian Bidang Ilmu</div>
        <?php
            $lin = $filterUnknown($c['linearitas']['labels'] ?? [], $c['linearitas']['data'] ?? []);
            $linTotal = $sumData($lin['data']);
        ?>
        <table>
            <thead><tr><th>Kategori</th><th class="right" style="width:20%;">Jumlah</th><th class="right" style="width:20%;">Persentase</th></tr></thead>
            <tbody>
            <?php $__currentLoopData = $lin['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php $val=(int)($lin['data'][$i] ?? 0); ?>
                <tr><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($val)); ?></td><td class="right"><?php echo e($linTotal>0 ? (round(($val/$linTotal)*100).'%') : '-'); ?></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </tbody>
        </table>
    </div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Persebaran Wilayah Kerja</h2>
    <?php $tw = $filterUnknown($c['top_wilayah']['labels'] ?? [], $c['top_wilayah']['data'] ?? []); ?>
    <table>
        <thead><tr><th class="center" style="width:7%;">No</th><th>Wilayah</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = $tw['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td class="center"><?php echo e($i+1); ?></td><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($tw['data'][$i] ?? 0)); ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Studi Lanjut</h2>
    <?php $tk = $filterUnknown($c['top_kampus']['labels'] ?? [], $c['top_kampus']['data'] ?? []); ?>
    <table>
        <thead><tr><th class="center" style="width:7%;">No</th><th>Nama Kampus</th><th class="right" style="width:18%;">Jumlah</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = $tk['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td class="center"><?php echo e($i+1); ?></td><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($tk['data'][$i] ?? 0)); ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
</div>

<div class="section">
    <h2>Statistik Gaji Alumni</h2>
    <div style="font-weight:800; margin-bottom:6px;">Distribusi Rentang Gaji Alumni</div>
    <?php
        $sdl = (array)($c['salary_distribution']['labels'] ?? []);
        $sdd = (array)($c['salary_distribution']['data'] ?? []);
        $sdFiltered = $filterUnknown($sdl, $sdd);
        $unknownSalary = (int)($c['salary_distribution']['total_unknown'] ?? 0);
    ?>
    <table>
        <thead><tr><th>Rentang Gaji</th><th class="right" style="width:20%;">Jumlah</th></tr></thead>
        <tbody>
        <?php $__currentLoopData = $sdFiltered['labels']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $lab): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <tr><td><?php echo e($lab); ?></td><td class="right"><?php echo e($fmtNum($sdFiltered['data'][$i] ?? 0)); ?></td></tr>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </tbody>
    </table>
    <?php if($unknownSalary > 0): ?>
        <div class="note">Data gaji tidak diketahui: <?php echo e($fmtNum($unknownSalary)); ?> alumni</div>
    <?php endif; ?>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Tren Alumni</h2>
    <?php
        $trendLabels = (array)($c['tren_angkatan']['labels'] ?? []);
        $trendTotal = (array)($c['tren_angkatan']['total'] ?? []);
        $trendBekerja = (array)($c['tren_angkatan']['bekerja'] ?? []);
        $trendBelum = (array)($c['tren_angkatan']['belum_bekerja'] ?? []);
        $len = min(count($trendLabels), count($trendTotal));
    ?>

    <div style="margin-bottom:10px;">
        <div style="font-weight:800; margin-bottom:6px;">Tren Alumni per Angkatan</div>
        <table>
            <thead><tr><th style="width:22%;">Angkatan</th><th class="right">Jumlah Alumni</th></tr></thead>
            <tbody>
            <?php for($i=0; $i<$len; $i++): ?>
                <tr><td><?php echo e($trendLabels[$i]); ?></td><td class="right"><?php echo e($fmtNum($trendTotal[$i] ?? 0)); ?></td></tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-bottom:0;">
        <div style="font-weight:800; margin-bottom:6px;">Tren Keterserapan Kerja per Angkatan</div>
        <table>
            <thead><tr><th style="width:22%;">Angkatan</th><th class="right">Bekerja</th><th class="right">Belum Bekerja</th></tr></thead>
            <tbody>
            <?php for($i=0; $i<$len; $i++): ?>
                <tr>
                    <td><?php echo e($trendLabels[$i]); ?></td>
                    <td class="right"><?php echo e($fmtNum($trendBekerja[$i] ?? 0)); ?></td>
                    <td class="right"><?php echo e($fmtNum($trendBelum[$i] ?? 0)); ?></td>
                </tr>
            <?php endfor; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Kualitas Data</h2>
    <?php
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
    ?>
    <table>
        <thead><tr><th>Komponen Data</th><th class="right" style="width:20%;">Data Valid</th><th class="right" style="width:20%;">Tidak Diketahui</th></tr></thead>
        <tbody>
        <tr><td>Jenis Kelamin</td><td class="right"><?php echo e($fmtNum($genderValid)); ?></td><td class="right"><?php echo e($fmtNum($genderUnknown)); ?></td></tr>
        <tr><td>TOEFL</td><td class="right"><?php echo e($fmtNum($toeflValid)); ?></td><td class="right"><?php echo e($fmtNum($toeflUnknown)); ?></td></tr>
        <tr><td>Gaji</td><td class="right"><?php echo e($fmtNum($salaryValid)); ?></td><td class="right"><?php echo e($fmtNum($salaryUnknown)); ?></td></tr>
        <tr><td>Bidang Pekerjaan</td><td class="right"><?php echo e($fmtNum($bidangValid)); ?></td><td class="right"><?php echo e($fmtNum($bidangUnknown)); ?></td></tr>
        <tr><td>Wilayah Kerja</td><td class="right"><?php echo e($fmtNum($wilValid)); ?></td><td class="right"><?php echo e($fmtNum($wilUnknown)); ?></td></tr>
        </tbody>
    </table>
    <div class="note">Catatan: komponen “Bidang Pekerjaan” dan “Wilayah Kerja” dihitung dari data pekerjaan aktif/utama pada filter saat ini.</div>
</div>

<div class="page-break"></div>
<div class="section">
    <h2>Kesimpulan & Rekomendasi</h2>
    <?php
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
    ?>
    <div style="font-weight:800; margin-bottom:6px;">Kesimpulan</div>
    <ol style="margin:0; padding-left:18px;">
        <li>Jumlah alumni terdata sebanyak <?php echo e($fmtNum($k['total_alumni'] ?? 0)); ?> orang.</li>
        <li>Alumni bekerja sebanyak <?php echo e($fmtNum($k['bekerja'] ?? 0)); ?> orang.</li>
        <?php if(!empty($topBidang)): ?><li>Bidang pekerjaan terbanyak adalah <?php echo e($topBidang); ?>.</li><?php endif; ?>
        <?php if(!empty($topWil)): ?><li>Wilayah kerja terbanyak adalah <?php echo e($topWil); ?>.</li><?php endif; ?>
        <?php if(($salaryUnknown ?? 0) > 0): ?><li>Data yang masih perlu dilengkapi: gaji (tidak diketahui <?php echo e($fmtNum($salaryUnknown)); ?> alumni).</li><?php endif; ?>
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
        Laporan Statistik Alumni • Dicetak <?php echo e(($printedAt ?? now())->format('d-m-Y H:i')); ?>

    </div>
</div>

</body>
</html>
<?php /**PATH D:\Aplikasi_Skripsi\gis_alumni_5\resources\views/admin/statistik/pdf.blade.php ENDPATH**/ ?>