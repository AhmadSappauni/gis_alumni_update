<?php

namespace App\Exports\Sheets;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RingkasanUmumSheet implements FromArray, WithTitle, WithStyles
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

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A2')->getFont()->setBold(true);
        $sheet->getColumnDimension('A')->setWidth(38);
        $sheet->getColumnDimension('B')->setWidth(60);

        return [
            8 => ['font' => ['bold' => true]],
            9 => ['font' => ['bold' => true], 'fill' => ['fillType' => 'solid', 'color' => ['rgb' => 'F1F5F9']]],
        ];
    }
}
