<?php

namespace App\Exports\Sheets;

use App\Models\RiwayatPekerjaan;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;

class DataAlumniDetailSheet implements FromArray, WithHeadings, WithTitle, WithEvents
{
    public function __construct(protected Collection $alumni)
    {
    }

    public function title(): string
    {
        return 'Data Alumni Detail';
    }

    protected function pickMainJob($jobs): ?RiwayatPekerjaan
    {
        if (!$jobs || $jobs->isEmpty()) return null;

        $workingJobs = $jobs->filter(function ($job) {
            $status = strtolower(trim((string) ($job->status_kerja ?? '')));
            return $status === 'bekerja' || $status === 'wirausaha';
        });

        $pool = $workingJobs->isNotEmpty() ? $workingJobs : $jobs;

        return $pool->sort(function ($a, $b) {
            $rankA = strtolower(trim((string) $a->status_karir)) === 'utama' ? 0 : 1;
            $rankB = strtolower(trim((string) $b->status_karir)) === 'utama' ? 0 : 1;
            if ($rankA !== $rankB) return $rankA <=> $rankB;

            $currentA = $a->is_current ? 0 : 1;
            $currentB = $b->is_current ? 0 : 1;
            if ($currentA !== $currentB) return $currentA <=> $currentB;

            $idA = (int) ($a->id ?? 0);
            $idB = (int) ($b->id ?? 0);
            return $idB <=> $idA;
        })->first();
    }

    protected function getCompanyLocation(?RiwayatPekerjaan $job): ?object
    {
        if (!$job) return null;
        // Gunakan lokasiAktif saja — konsisten dengan StatistikController::getLokasiPerusahaan().
        return $job->perusahaan?->lokasiAktif;
    }

    protected function deriveStatus($alumni): string
    {
        $jobs = $alumni->pekerjaan ?? collect();
        $hasStudi = ($alumni->studiLanjut && $alumni->studiLanjut->isNotEmpty());

        $workingAktif = $jobs->filter(function ($job) {
            $status = strtolower(trim((string) ($job->status_kerja ?? '')));
            if (!($status === 'bekerja' || $status === 'wirausaha')) return false;
            $karir = strtolower(trim((string) ($job->status_karir ?? '')));
            return $karir === 'utama' || (bool) $job->is_current;
        });

        $isBekerja = $workingAktif->isNotEmpty();
        return $hasStudi ? 'Studi Lanjut' : ($isBekerja ? 'Bekerja' : 'Belum Bekerja');
    }

    public function headings(): array
    {
        return [
            'No',
            'Nama',
            'NIM',
            'Angkatan',
            'Tahun Lulus',
            'Jenis Kelamin',
            'Status Alumni',
            'Status Kerja',
            'Bidang Pekerjaan',
            'Instansi/Perusahaan',
            'Wilayah Kerja',
            'Masa Tunggu (bulan)',
            'Nilai TOEFL',
            'Gaji Nominal',
            'Kesesuaian Bidang Ilmu',
            'Studi Lanjut',
            'Kampus Studi Lanjut',
        ];
    }

    public function array(): array
    {
        $rows = [];
        $i = 1;

        foreach ($this->alumni as $alumni) {
            $jobs = $alumni->pekerjaan ?? collect();
            $job = $this->pickMainJob($jobs);
            $lokasi = $this->getCompanyLocation($job);

            $angkatan = $alumni->akademik?->angkatan;
            $tahunLulus = $alumni->akademik?->tahun_lulus;
            $toefl = $alumni->akademik?->nilai_toefl;

            $statusAlumni = $this->deriveStatus($alumni);
            $statusKerja = $job?->status_kerja ?: '-';
            $bidang = $job?->bidang_pekerjaan ?: '-';
            $instansi = $job?->perusahaan?->nama_perusahaan ?: '-';
            $wilayah = trim((string) ($lokasi?->kota ?? '')) ?: (trim((string) ($lokasi?->provinsi ?? '')) ?: '-');
            $masaTunggu = $job?->masa_tunggu ?? '-';
            $gaji = $job?->gaji_nominal ?? '-';
            $linearitas = $job?->perusahaan?->linearitas ?? '-';

            $studi = ($alumni->studiLanjut && $alumni->studiLanjut->isNotEmpty()) ? 'Ya' : 'Tidak';
            $kampus = '-';
            if ($alumni->studiLanjut && $alumni->studiLanjut->isNotEmpty()) {
                $first = $alumni->studiLanjut->sortByDesc('tahun_masuk')->first();
                $kampus = $first?->nama_kampus ?? '-';
            }

            $rows[] = [
                $i++,
                (string) ($alumni->nama_lengkap ?? ''),
                (string) ($alumni->nim ?? ''),
                $angkatan ?? '-',
                $tahunLulus ?? '-',
                (string) ($alumni->jenis_kelamin ?? '-'),
                $statusAlumni,
                (string) $statusKerja,
                (string) $bidang,
                (string) $instansi,
                (string) $wilayah,
                $masaTunggu,
                $toefl ?? '-',
                $gaji,
                (string) $linearitas,
                $studi,
                (string) $kampus,
            ];
        }

        return $rows;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $event->sheet->freezePane('A2');
            },
        ];
    }
}

