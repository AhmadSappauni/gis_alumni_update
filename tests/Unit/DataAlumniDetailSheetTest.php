<?php

namespace Tests\Unit;

use App\Exports\Sheets\DataAlumniDetailSheet;
use App\Models\AlamatAlumni;
use App\Models\Alumni;
use App\Models\AlumniAkademik;
use App\Models\LokasiPerusahaan;
use App\Models\Perusahaan;
use App\Models\RiwayatPekerjaan;
use PHPUnit\Framework\TestCase;

class DataAlumniDetailSheetTest extends TestCase
{
    public function test_historical_working_job_is_exported_as_belum_bekerja_without_job_details(): void
    {
        $historicalJob = $this->makeJob(
            id: 10,
            isCurrent: false,
            statusKerja: 'Bekerja',
            statusKarir: 'Riwayat',
            companyName: 'Perusahaan Historis',
        );
        $alumni = $this->makeAlumni(collect([$historicalJob]));

        $row = (new DataAlumniDetailSheet(collect([$alumni])))->array()[0];

        $this->assertSame('Belum Bekerja', $row[6]);
        $this->assertSame('Belum Bekerja', $row[7]);
        $this->assertSame('-', $row[8]);
        $this->assertSame('-', $row[9]);
        $this->assertSame('-', $row[10]);
        $this->assertSame('-', $row[11]);
        $this->assertSame('-', $row[13]);
        $this->assertSame('-', $row[14]);
    }

    public function test_active_working_job_is_exported_normally_and_historical_job_is_ignored(): void
    {
        $historicalJob = $this->makeJob(
            id: 20,
            isCurrent: false,
            statusKerja: 'Bekerja',
            statusKarir: 'Utama',
            companyName: 'Perusahaan Historis',
        );
        $activeJob = $this->makeJob(
            id: 15,
            isCurrent: true,
            statusKerja: 'Bekerja',
            statusKarir: 'Sampingan',
            companyName: 'Perusahaan Aktif',
        );
        $alumni = $this->makeAlumni(collect([$historicalJob, $activeJob]));

        $row = (new DataAlumniDetailSheet(collect([$alumni])))->array()[0];

        $this->assertSame('Bekerja', $row[6]);
        $this->assertSame('Bekerja', $row[7]);
        $this->assertSame('Teknologi Informasi', $row[8]);
        $this->assertSame('Perusahaan Aktif', $row[9]);
        $this->assertSame('Banjarmasin', $row[10]);
        $this->assertSame(3, $row[11]);
        $this->assertSame(5000000, $row[13]);
        $this->assertSame('Sangat Erat', $row[14]);
    }

    private function makeAlumni($jobs): Alumni
    {
        $alumni = new Alumni([
            'nim' => '22000001',
            'nama_lengkap' => 'Alumni Export',
            'jenis_kelamin' => 'L',
        ]);

        $alumni->setRelation('akademik', new AlumniAkademik([
            'angkatan' => 2022,
            'tahun_lulus' => 2026,
            'nilai_toefl' => 525,
        ]));
        $alumni->setRelation('alamat', new AlamatAlumni([
            'kota' => 'Banjarbaru',
            'provinsi' => 'Kalimantan Selatan',
        ]));
        $alumni->setRelation('pekerjaan', $jobs);
        $alumni->setRelation('studiLanjut', collect());

        return $alumni;
    }

    private function makeJob(
        int $id,
        bool $isCurrent,
        string $statusKerja,
        string $statusKarir,
        string $companyName,
    ): RiwayatPekerjaan {
        $job = new RiwayatPekerjaan([
            'jabatan' => 'Software Engineer',
            'bidang_pekerjaan' => 'Teknologi Informasi',
            'status_kerja' => $statusKerja,
            'status_karir' => $statusKarir,
            'is_current' => $isCurrent,
            'masa_tunggu' => 3,
            'gaji_nominal' => 5000000,
        ]);
        $job->id = $id;

        $company = new Perusahaan([
            'nama_perusahaan' => $companyName,
            'linearitas' => 'Sangat Erat',
        ]);
        $company->setRelation('lokasiAktif', new LokasiPerusahaan([
            'kota' => 'Banjarmasin',
            'provinsi' => 'Kalimantan Selatan',
        ]));

        $job->setRelation('perusahaan', $company);

        return $job;
    }
}
