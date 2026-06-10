<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StatistikSheetFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class TrenAlumniSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(protected array $payload)
    {
    }

    public function title(): string
    {
        return 'Tren Alumni';
    }

    public function array(): array
    {
        $c = (array) ($this->payload['charts'] ?? []);
        $tren = (array) ($c['tren_angkatan'] ?? []);

        $labels = (array) ($tren['labels'] ?? []);
        $total = (array) ($tren['total'] ?? []);
        $bekerja = (array) ($tren['bekerja'] ?? []);
        $belum = (array) ($tren['belum_bekerja'] ?? []);

        $len = min(count($labels), count($total));

        $rows = [];

        $rows[] = ['Tren Alumni per Angkatan'];
        $rows[] = ['Angkatan', 'Jumlah Alumni'];
        for ($i = 0; $i < $len; $i++) {
            $rows[] = [(string) ($labels[$i] ?? ''), (int) ($total[$i] ?? 0)];
        }
        $rows[] = [];

        $rows[] = ['Tren Keterserapan Kerja per Angkatan'];
        $rows[] = ['Angkatan', 'Bekerja', 'Belum Bekerja'];
        for ($i = 0; $i < $len; $i++) {
            $rows[] = [(string) ($labels[$i] ?? ''), (int) ($bekerja[$i] ?? 0), (int) ($belum[$i] ?? 0)];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                StatistikSheetFormatter::styleSectionedSheet($event->sheet->getDelegate(), [
                    'A' => 20,
                    'B' => 20,
                    'C' => 20,
                ]);
            },
        ];
    }
}
