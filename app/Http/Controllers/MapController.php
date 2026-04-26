<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\RiwayatPekerjaan;
use App\Models\StudiLanjut;
use Illuminate\Support\Collection;

class MapController extends Controller
{
    private function getLokasiPerusahaan(?\App\Models\RiwayatPekerjaan $job): ?object
    {
        if (!$job) {
            return null;
        }

        return $job->perusahaan?->lokasiAktif
            ?? $job->perusahaan?->lokasi->sortByDesc('id')->first();
    }

    private function getStatusKarirLower(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    public function index()
    {
        $markers = collect();
        $studiLanjutMarkers = collect();

        /*
        |--------------------------------------------------------------------------
        | 1. ALUMNI YANG BEKERJA
        |--------------------------------------------------------------------------
        | Ambil pekerjaan aktif sekarang
        | Titik marker = lokasi perusahaan
        */

        $pekerja = RiwayatPekerjaan::with([
            'alumni.akademik',
            'alumni.alamat',
            'perusahaan.lokasiAktif',
            'perusahaan.lokasi'
        ])
        ->where('is_current', true)
        ->where('status_kerja', 'Bekerja')
        ->get();

        $pekerjaGrouped = $pekerja->groupBy('alumni_id');

        $pekerjaPerAlumni = $pekerjaGrouped
            ->map(function ($jobs) {
                return $jobs->sort(function ($a, $b) {
                    $rankA = strtolower((string) ($a->status_karir ?? '')) === 'utama' ? 0 : 1;
                    $rankB = strtolower((string) ($b->status_karir ?? '')) === 'utama' ? 0 : 1;

                    if ($rankA !== $rankB) {
                        return $rankA <=> $rankB;
                    }

                    $mulaiA = $a->tanggal_mulai ? strtotime((string) $a->tanggal_mulai) : null;
                    $mulaiB = $b->tanggal_mulai ? strtotime((string) $b->tanggal_mulai) : null;

                    if ($mulaiA !== null || $mulaiB !== null) {
                        $mulaiA = $mulaiA ?? 0;
                        $mulaiB = $mulaiB ?? 0;
                        if ($mulaiA !== $mulaiB) {
                            return $mulaiB <=> $mulaiA; // desc
                        }
                    }

                    $createdA = $a->created_at ? strtotime((string) $a->created_at) : 0;
                    $createdB = $b->created_at ? strtotime((string) $b->created_at) : 0;
                    if ($createdA !== $createdB) {
                        return $createdB <=> $createdA;
                    }

                    return (int) $b->id <=> (int) $a->id;
                })->first();
            })
            ->values();

        $workingAlumniIds = $pekerjaPerAlumni
            ->pluck('alumni_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        foreach ($pekerjaPerAlumni as $job) {

            $lokasi = $this->getLokasiPerusahaan($job);

            if (!$lokasi || !$lokasi->latitude || !$lokasi->longitude) {
                continue;
            }

            $jobsAlumni = $pekerjaGrouped->get($job->alumni_id, collect());

            $pekerjaanUtama = [
                'id' => $job->id,
                'perusahaan' => $job->perusahaan?->nama_perusahaan,
                'jabatan' => $job->jabatan,
                'status_karir' => $job->status_karir,
                'latitude' => (float) $lokasi->latitude,
                'longitude' => (float) $lokasi->longitude,
            ];

            $pekerjaanLainnya = $jobsAlumni
                ->filter(function ($row) use ($job) {
                    return (int) $row->id !== (int) $job->id;
                })
                ->map(function ($row) {
                    $lokasi = $this->getLokasiPerusahaan($row);

                    if (!$lokasi || !$lokasi->latitude || !$lokasi->longitude) {
                        return null;
                    }

                    $statusKarirLower = $this->getStatusKarirLower($row->status_karir);
                    if ($statusKarirLower !== 'sampingan') {
                        return null;
                    }

                    return [
                        'id' => $row->id,
                        'perusahaan' => $row->perusahaan?->nama_perusahaan,
                        'jabatan' => $row->jabatan,
                        'status_karir' => $row->status_karir,
                        'bidang_pekerjaan' => $row->bidang_pekerjaan,
                        'kota' => $lokasi->kota,
                        'provinsi' => $lokasi->provinsi,
                        'latitude' => (float) $lokasi->latitude,
                        'longitude' => (float) $lokasi->longitude,
                    ];
                })
                ->filter()
                ->values();

            $markers->push([
                'id'            => $job->alumni?->id,
                'alumni_id'     => $job->alumni?->id,
                'nim'           => $job->alumni?->nim,
                'nama'          => $job->alumni?->nama_lengkap,
                'foto'          => $job->alumni?->foto_profil,
                'tahun_lulus'   => $job->alumni?->akademik?->tahun_lulus,
                'angkatan'      => $job->alumni?->akademik?->angkatan,

                'status'        => 'Bekerja',
                'status_icon'   => 'working',

                'latitude'      => (float) $lokasi->latitude,
                'longitude'     => (float) $lokasi->longitude,

                'kota'          => $lokasi->kota,
                'provinsi'      => $lokasi->provinsi,
                'alamat'        => $lokasi->alamat_lengkap,

                'perusahaan'    => $job->perusahaan?->nama_perusahaan,
                'jabatan'       => $job->jabatan,
                'bidang'        => $job->bidang_pekerjaan,
                'linearitas'    => $job->perusahaan?->linearitas,
                'gaji'          => $job->gaji_nominal,
                'masa_tunggu'   => $job->masa_tunggu,

                'pekerjaan_utama' => $pekerjaanUtama,
                'pekerjaan_lainnya' => $pekerjaanLainnya,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 2. ALUMNI BELUM BEKERJA
        |--------------------------------------------------------------------------
        | Ambil alumni yang tidak punya pekerjaan aktif bekerja
        | Titik marker = domisili alumni
        */

        $belumKerjaQuery = Alumni::with([
            'akademik',
            'alamat'
        ]);

        if (!empty($workingAlumniIds)) {
            $belumKerjaQuery->whereNotIn('id', $workingAlumniIds);
        }

        $belumKerja = $belumKerjaQuery->get();

        foreach ($belumKerja as $alumni) {

            $alamat = $alumni->alamat;

            if (!$alamat || !$alamat->latitude || !$alamat->longitude) {
                continue;
            }

            $markers->push([
                'id'            => $alumni->id,
                'alumni_id'     => $alumni->id,
                'nim'           => $alumni->nim,
                'nama'          => $alumni->nama_lengkap,
                'foto'          => $alumni->foto_profil,
                'tahun_lulus'   => $alumni->akademik?->tahun_lulus,
                'angkatan'      => $alumni->akademik?->angkatan,

                'status'        => 'Belum Bekerja',
                'status_icon'   => 'unemployed',

                'latitude'      => (float) $alamat->latitude,
                'longitude'     => (float) $alamat->longitude,

                'kota'          => $alamat->kota,
                'provinsi'      => $alamat->provinsi,
                'alamat'        => $alamat->alamat_lengkap,

                'perusahaan'    => null,
                'jabatan'       => null,
                'bidang'        => null,
                'linearitas'    => null,
                'gaji'          => null,
                'masa_tunggu'   => null,

                'pekerjaan_utama' => null,
                'pekerjaan_lainnya' => [],
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | 3. SORTING
        |--------------------------------------------------------------------------
        */

        $markers = $markers
            ->sortByDesc(function ($item) {
                return $item['status'] === 'Bekerja' ? 1 : 0;
            })
            ->values();

        /*
        |--------------------------------------------------------------------------
        | 4. STUDI LANJUT
        |--------------------------------------------------------------------------
        | Marker khusus lokasi kampus/universitas dari tabel studi_lanjut
        */

        $studiLanjutRows = StudiLanjut::with([
            'alumni.akademik'
        ])
        ->whereNotNull('latitude')
        ->whereNotNull('longitude')
        ->get();

        foreach ($studiLanjutRows as $row) {
            $alumni = $row->alumni;

            if (!$alumni) {
                continue;
            }

            if (!$row->latitude || !$row->longitude) {
                continue;
            }

            $studiLanjutMarkers->push([
                'alumni_id' => $alumni->id,
                'nama_lengkap' => $alumni->nama_lengkap,
                'nim' => $alumni->nim,
                'foto' => $alumni->foto_profil,
                'tahun_lulus_alumni' => $alumni->akademik?->tahun_lulus,
                'angkatan' => $alumni->akademik?->angkatan,

                'kampus' => $row->kampus,
                'alamat_kampus' => $row->alamat_kampus,
                'kota_kampus' => $row->kota_kampus,
                'provinsi_kampus' => $row->provinsi_kampus,
                'latitude' => (float) $row->latitude,
                'longitude' => (float) $row->longitude,
                'jenjang' => $row->jenjang,
                'program_studi' => $row->program_studi,
                'tahun_masuk' => $row->tahun_masuk,
                'tahun_lulus_studi' => $row->tahun_lulus,
                'status' => $row->status,
            ]);
        }

        $workingCount = $markers
            ->where('status', 'Bekerja')
            ->pluck('alumni_id')
            ->filter()
            ->unique()
            ->count();

        $belumCount = $markers
            ->where('status', 'Belum Bekerja')
            ->pluck('alumni_id')
            ->filter()
            ->unique()
            ->count();

        $multiJobCount = $markers
            ->filter(function ($item) {
                $pekerjaanLainnya = $item['pekerjaan_lainnya'] ?? [];
                return is_array($pekerjaanLainnya) && count($pekerjaanLainnya) > 0;
            })
            ->pluck('alumni_id')
            ->filter()
            ->unique()
            ->count();

        $studiCount = $studiLanjutMarkers
            ->pluck('alumni_id')
            ->filter()
            ->unique()
            ->count();

        $totalAlumni = collect()
            ->merge($markers->pluck('alumni_id'))
            ->merge($studiLanjutMarkers->pluck('alumni_id'))
            ->filter()
            ->unique()
            ->count();

        $mapPayload = [
            'total_alumni' => $totalAlumni,
            'total_bekerja' => $workingCount,
            'total_belum_bekerja' => $belumCount,
            'total_multi_job' => $multiJobCount,
            'total_studi_lanjut' => $studiCount,
            'markers' => $markers,
            'studi_lanjut_markers' => $studiLanjutMarkers,
        ];

        return view('index', [
            'dataPekerjaan' => $markers,
            'studiLanjutMarkers' => $studiLanjutMarkers,
            'mapSummary' => [
                'total_alumni' => $totalAlumni,
                'total_bekerja' => $workingCount,
                'total_belum_bekerja' => $belumCount,
                'total_multi_job' => $multiJobCount,
                'total_studi_lanjut' => $studiCount,
            ],
            'mapPayload' => $mapPayload,
        ]);
    }
}
