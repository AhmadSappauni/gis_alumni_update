<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class KetenagakerjaanSheet implements FromArray, WithTitle, WithStyles
{
    public function __construct(
        protected array $payload,
        protected bool $showUnknown,
    ) {
    }

    public function title(): string
    {
        return 'Ketenagakerjaan';
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

    public function array(): array
    {
        $k = (array) ($this->payload['kpis'] ?? []);
        $c = (array) ($this->payload['charts'] ?? []);

        $rows = [];

        // 1) Status alumni
        $rows[] = ['Status Alumni'];
        $rows[] = ['Status', 'Jumlah', 'Persentase'];
        $total = max(0, (int) ($k['total_alumni'] ?? 0));
        $status = [
            ['Bekerja', (int) ($k['bekerja'] ?? 0)],
            ['Belum Bekerja', (int) ($k['belum_bekerja'] ?? 0)],
            ['Studi Lanjut', (int) ($k['studi_lanjut'] ?? 0)],
        ];
        foreach ($status as [$label, $val]) {
            $pct = $total > 0 ? round(($val / $total) * 100) . '%' : '-';
            $rows[] = [$label, $val, $pct];
        }
        $rows[] = [];

        // 2) Masa tunggu
        $rows[] = ['Masa Tunggu Kerja'];
        $rows[] = ['Rentang', 'Jumlah'];
        $mt = $this->filterUnknown((array) ($c['masa_tunggu']['labels'] ?? []), (array) ($c['masa_tunggu']['data'] ?? []));
        foreach (($mt['labels'] ?? []) as $i => $lab) {
            $rows[] = [(string) $lab, (int) (($mt['data'][$i] ?? 0))];
        }
        $rows[] = [];

        // 3) Top 10 company
        $rows[] = ['Top 10 Perusahaan/Instansi Tujuan'];
        $rows[] = ['No', 'Instansi/Perusahaan', 'Jumlah'];
        $tc = $this->filterUnknown((array) ($c['top_company']['labels'] ?? []), (array) ($c['top_company']['data'] ?? []));
        $max = min(10, count($tc['labels'] ?? []));
        for ($i = 0; $i < $max; $i++) {
            $rows[] = [$i + 1, (string) ($tc['labels'][$i] ?? ''), (int) ($tc['data'][$i] ?? 0)];
        }
        $rows[] = [];

        // 4) Top 5 bidang
        $rows[] = ['Top 5 Bidang Pekerjaan'];
        $rows[] = ['No', 'Bidang Pekerjaan', 'Jumlah'];
        $tb = $this->filterUnknown((array) ($c['top_bidang']['labels'] ?? []), (array) ($c['top_bidang']['data'] ?? []));
        $max = min(5, count($tb['labels'] ?? []));
        for ($i = 0; $i < $max; $i++) {
            $rows[] = [$i + 1, (string) ($tb['labels'][$i] ?? ''), (int) ($tb['data'][$i] ?? 0)];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getColumnDimension('A')->setWidth(28);
        $sheet->getColumnDimension('B')->setWidth(48);
        $sheet->getColumnDimension('C')->setWidth(16);

        return [
            1 => ['font' => ['bold' => true, 'size' => 12]],
        ];
    }
}

