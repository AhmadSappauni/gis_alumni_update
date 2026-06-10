<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StatistikSheetFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class KualitasDataSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        protected array $payload,
        protected bool $showUnknown,
    ) {
    }

    public function title(): string
    {
        return 'Kualitas Data';
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

    protected function calcValidUnknown(array $labels, array $data): array
    {
        $len = min(count($labels), count($data));
        $labels = array_slice($labels, 0, $len);
        $data = array_slice($data, 0, $len);

        $unknown = 0;
        $total = 0;
        for ($i = 0; $i < $len; $i++) {
            $v = (int) ($data[$i] ?? 0);
            $total += $v;
            if ($this->isUnknownLabel($labels[$i])) {
                $unknown += $v;
            }
        }
        return ['valid' => max(0, $total - $unknown), 'unknown' => $unknown];
    }

    public function array(): array
    {
        $c = (array) ($this->payload['charts'] ?? []);

        $gender = $this->calcValidUnknown((array) ($c['gender']['labels'] ?? []), (array) ($c['gender']['data'] ?? []));
        $toefl = $this->calcValidUnknown((array) ($c['toefl_dist']['labels'] ?? []), (array) ($c['toefl_dist']['data'] ?? []));

        $sd = (array) ($c['salary_distribution'] ?? []);
        $salaryValid = (int) ($sd['total_valid'] ?? 0);
        $salaryUnknown = (int) ($sd['total_unknown'] ?? 0);

        $bidang = $this->calcValidUnknown((array) ($c['top_bidang']['labels'] ?? []), (array) ($c['top_bidang']['data'] ?? []));
        $wil = $this->calcValidUnknown((array) ($c['top_wilayah']['labels'] ?? []), (array) ($c['top_wilayah']['data'] ?? []));

        $rows = [];
        $rows[] = ['Kualitas Data'];
        $rows[] = ['Komponen Data', 'Data Valid', 'Tidak Diketahui'];
        $rows[] = ['Jenis Kelamin', $gender['valid'], $gender['unknown']];
        $rows[] = ['TOEFL', $toefl['valid'], $toefl['unknown']];
        $rows[] = ['Gaji', $salaryValid, $salaryUnknown];
        $rows[] = ['Bidang Pekerjaan', $bidang['valid'], $bidang['unknown']];
        $rows[] = ['Wilayah Kerja', $wil['valid'], $wil['unknown']];
        $rows[] = [];
        $rows[] = ['Catatan', 'Komponen Bidang Pekerjaan dan Wilayah Kerja dihitung dari data pekerjaan aktif/utama pada filter saat ini.'];

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                StatistikSheetFormatter::styleSectionedSheet($event->sheet->getDelegate(), [
                    'A' => 30,
                    'B' => 18,
                    'C' => 20,
                ]);
            },
        ];
    }
}
