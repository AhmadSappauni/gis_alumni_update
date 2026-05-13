<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithTitle;

class ProfilRelevansiSheet implements FromArray, WithTitle
{
    public function __construct(
        protected array $payload,
        protected bool $showUnknown,
    ) {
    }

    public function title(): string
    {
        return 'Profil Relevansi';
    }

    protected function isUnknownLabel($label): bool
    {
        $s = strtolower(trim((string) ($label ?? '')));
        if ($s === '' || $s === '-' || $s === 'null') return true;
        if (str_contains($s, 'tidak diketahui')) return true;
        if (str_contains($s, 'belum diisi')) return true;
        if (str_contains($s, 'kosong')) return true;
        return false;
    }

    protected function filterUnknown(array $labels, array $data): array
    {
        $len = min(count($labels), count($data));
        $labels = array_slice($labels, 0, $len);
        $data = array_slice($data, 0, $len);

        if ($this->showUnknown) {
            return ['labels' => $labels, 'data' => $data];
        }

        $outL = [];
        $outD = [];
        for ($i = 0; $i < $len; $i++) {
            if ($this->isUnknownLabel($labels[$i])) continue;
            $outL[] = $labels[$i];
            $outD[] = $data[$i];
        }
        return ['labels' => $outL, 'data' => $outD];
    }

    protected function sum(array $values): int
    {
        $t = 0;
        foreach ($values as $v) {
            $t += (int) $v;
        }
        return $t;
    }

    public function array(): array
    {
        $c = (array) ($this->payload['charts'] ?? []);

        $rows = [];

        // 1) Gender
        $rows[] = ['Jenis Kelamin'];
        $rows[] = ['Jenis Kelamin', 'Jumlah', 'Persentase'];
        $g = $this->filterUnknown((array) ($c['gender']['labels'] ?? []), (array) ($c['gender']['data'] ?? []));
        $gTotal = max(0, $this->sum((array) ($g['data'] ?? [])));
        foreach (($g['labels'] ?? []) as $i => $lab) {
            $val = (int) (($g['data'][$i] ?? 0));
            $pct = $gTotal > 0 ? round(($val / $gTotal) * 100) . '%' : '-';
            $rows[] = [(string) $lab, $val, $pct];
        }
        $rows[] = [];

        // 2) TOEFL
        $rows[] = ['Distribusi Nilai TOEFL'];
        $rows[] = ['Kelompok Nilai', 'Jumlah'];
        $to = $this->filterUnknown((array) ($c['toefl_dist']['labels'] ?? []), (array) ($c['toefl_dist']['data'] ?? []));
        foreach (($to['labels'] ?? []) as $i => $lab) {
            $rows[] = [(string) $lab, (int) ($to['data'][$i] ?? 0)];
        }
        $rows[] = [];

        // 3) Linearitas
        $rows[] = ['Kesesuaian Bidang Ilmu'];
        $rows[] = ['Kategori', 'Jumlah', 'Persentase'];
        $lin = $this->filterUnknown((array) ($c['linearitas']['labels'] ?? []), (array) ($c['linearitas']['data'] ?? []));
        $linTotal = max(0, $this->sum((array) ($lin['data'] ?? [])));
        foreach (($lin['labels'] ?? []) as $i => $lab) {
            $val = (int) ($lin['data'][$i] ?? 0);
            $pct = $linTotal > 0 ? round(($val / $linTotal) * 100) . '%' : '-';
            $rows[] = [(string) $lab, $val, $pct];
        }
        $rows[] = [];

        // 4) Persebaran wilayah
        $rows[] = ['Persebaran Wilayah Kerja'];
        $rows[] = ['No', 'Wilayah', 'Jumlah'];
        $tw = $this->filterUnknown((array) ($c['top_wilayah']['labels'] ?? []), (array) ($c['top_wilayah']['data'] ?? []));
        $max = min(50, count($tw['labels'] ?? []));
        for ($i = 0; $i < $max; $i++) {
            $rows[] = [$i + 1, (string) ($tw['labels'][$i] ?? ''), (int) ($tw['data'][$i] ?? 0)];
        }

        return $rows;
    }
}

