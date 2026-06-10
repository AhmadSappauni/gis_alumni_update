<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StatistikSheetFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class StatistikGajiSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        protected array $payload,
        protected bool $showUnknown,
    ) {
    }

    public function title(): string
    {
        return 'Statistik Gaji';
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
        $c = (array) ($this->payload['charts'] ?? []);
        $sd = (array) ($c['salary_distribution'] ?? []);

        $labels = (array) ($sd['labels'] ?? []);
        $data = (array) ($sd['data'] ?? []);
        $filtered = $this->filterUnknown($labels, $data);

        $totalValid = (int) ($sd['total_valid'] ?? 0);
        $totalUnknown = (int) ($sd['total_unknown'] ?? 0);

        $rows = [];
        $rows[] = ['Distribusi Rentang Gaji Alumni'];
        $rows[] = ['Rentang Gaji', 'Jumlah'];
        foreach (($filtered['labels'] ?? []) as $i => $lab) {
            $rows[] = [(string) $lab, (int) ($filtered['data'][$i] ?? 0)];
        }
        $rows[] = [];

        $rows[] = ['Ringkasan Data Gaji'];
        $rows[] = ['Keterangan', 'Jumlah'];
        $rows[] = ['Data Gaji Valid', $totalValid];
        $rows[] = ['Data Gaji Tidak Diketahui', $totalUnknown];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                StatistikSheetFormatter::styleSectionedSheet($event->sheet->getDelegate(), [
                    'A' => 34,
                    'B' => 18,
                ]);
            },
        ];
    }
}
