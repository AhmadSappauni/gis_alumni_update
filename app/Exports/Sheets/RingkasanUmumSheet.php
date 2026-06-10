<?php

namespace App\Exports\Sheets;

use App\Exports\Sheets\Concerns\StatistikSheetFormatter;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class RingkasanUmumSheet implements FromArray, WithTitle, WithEvents
{
    public function __construct(
        protected array $payload,
        protected array $filterRows,
        protected array $insights,
        protected $printedAt,
        protected string $printedBy,
        protected bool $showUnknown,
    ) {
    }

    public function title(): string
    {
        return 'Ringkasan Umum';
    }

    public function array(): array
    {
        $k = (array) ($this->payload['kpis'] ?? []);
        $c = (array) ($this->payload['charts'] ?? []);
        $sd = (array) ($c['salary_distribution'] ?? []);

        $salaryValid = (int) ($sd['total_valid'] ?? 0);
        $salaryUnknown = (int) ($sd['total_unknown'] ?? 0);

        $rows = [];
        $rows[] = ['LAPORAN STATISTIK ALUMNI'];
        $rows[] = ['Program Studi Pendidikan Komputer FKIP Universitas Lambung Mangkurat'];
        $rows[] = [];
        $rows[] = ['Tanggal Export', ($this->printedAt ?? now())->format('d-m-Y H:i')];
        $rows[] = ['Dicetak oleh', $this->printedBy ?: 'Admin'];
        $rows[] = [];

        $rows[] = ['Filter yang Digunakan'];
        $rows[] = ['Filter', 'Nilai'];
        foreach ($this->filterRows as $row) {
            $rows[] = [(string) ($row['Filter'] ?? ''), (string) ($row['Nilai'] ?? '')];
        }
        $rows[] = [];

        $rows[] = ['Ringkasan Umum'];
        $rows[] = ['Indikator', 'Nilai'];
        $rows[] = ['Total Alumni', (int) ($k['total_alumni'] ?? 0)];
        $rows[] = ['Alumni Bekerja', (int) ($k['bekerja'] ?? 0)];
        $rows[] = ['Belum Bekerja', (int) ($k['belum_bekerja'] ?? 0)];
        $rows[] = ['Studi Lanjut', (int) ($k['studi_lanjut'] ?? 0)];
        $rows[] = ['Multi-job', (int) ($k['multi_job'] ?? 0)];
        $rows[] = ['Rata-rata Masa Tunggu (bulan)', is_numeric($k['rata_masa_tunggu'] ?? null) ? (float) $k['rata_masa_tunggu'] : null];
        $rows[] = ['Rata-rata TOEFL', is_numeric($k['rata_toefl'] ?? null) ? (float) $k['rata_toefl'] : null];
        $rows[] = ['Data Gaji Valid', $salaryValid];
        $rows[] = ['Data Gaji Tidak Diketahui', $salaryUnknown];
        $rows[] = [];

        $rows[] = ['Insight Utama'];
        $rows[] = ['No', 'Insight'];
        $i = 1;
        foreach ($this->insights as $text) {
            $rows[] = [$i++, (string) $text];
        }

        if (empty($this->insights)) {
            $rows[] = [1, 'Insight belum tersedia karena data tidak mencukupi.'];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                StatistikSheetFormatter::styleSummarySheet($event->sheet->getDelegate());
            },
        ];
    }
}
