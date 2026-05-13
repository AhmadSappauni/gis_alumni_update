<?php

namespace App\Exports;

use App\Exports\Sheets\DataAlumniDetailSheet;
use App\Exports\Sheets\KetenagakerjaanSheet;
use App\Exports\Sheets\KualitasDataSheet;
use App\Exports\Sheets\ProfilRelevansiSheet;
use App\Exports\Sheets\RingkasanUmumSheet;
use App\Exports\Sheets\StatistikGajiSheet;
use App\Exports\Sheets\TrenAlumniSheet;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class StatistikAlumniExport implements WithMultipleSheets
{
    public function __construct(
        protected array $payload,
        protected array $filterRows,
        protected array $insights,
        protected $printedAt,
        protected string $printedBy,
        protected bool $showUnknown,
        protected array $filters,
        protected Collection $alumniDetail,
    ) {
    }

    public function sheets(): array
    {
        return [
            new RingkasanUmumSheet(
                payload: $this->payload,
                filterRows: $this->filterRows,
                insights: $this->insights,
                printedAt: $this->printedAt,
                printedBy: $this->printedBy,
                showUnknown: $this->showUnknown
            ),
            new KetenagakerjaanSheet(payload: $this->payload, showUnknown: $this->showUnknown),
            new ProfilRelevansiSheet(payload: $this->payload, showUnknown: $this->showUnknown),
            new StatistikGajiSheet(payload: $this->payload, showUnknown: $this->showUnknown),
            new TrenAlumniSheet(payload: $this->payload),
            new KualitasDataSheet(payload: $this->payload, showUnknown: $this->showUnknown),
            new DataAlumniDetailSheet(alumni: $this->alumniDetail),
        ];
    }
}

