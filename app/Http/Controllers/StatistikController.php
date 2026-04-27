<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\AlumniAkademik;
use App\Models\LokasiPerusahaan;
use App\Models\RiwayatPekerjaan;
use Illuminate\Http\Request;

class StatistikController extends Controller
{
    protected function getStatusKarirLower(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    protected function pilihPekerjaanUtama($jobs)
    {
        if (!$jobs || $jobs->isEmpty()) {
            return null;
        }

        $workingJobs = $jobs->filter(function ($job) {
            $status = strtolower(trim((string) ($job->status_kerja ?? '')));
            return $status === 'bekerja' || $status === 'wirausaha';
        });

        $pool = $workingJobs->isNotEmpty() ? $workingJobs : $jobs;

        return $pool->sort(function ($a, $b) {
            $rankA = $this->getStatusKarirLower($a->status_karir) === 'utama' ? 0 : 1;
            $rankB = $this->getStatusKarirLower($b->status_karir) === 'utama' ? 0 : 1;
            if ($rankA !== $rankB) {
                return $rankA <=> $rankB;
            }

            $currentA = $a->is_current ? 0 : 1;
            $currentB = $b->is_current ? 0 : 1;
            if ($currentA !== $currentB) {
                return $currentA <=> $currentB;
            }

            $mulaiA = $a->tanggal_mulai ? strtotime((string) $a->tanggal_mulai) : null;
            $mulaiB = $b->tanggal_mulai ? strtotime((string) $b->tanggal_mulai) : null;
            if ($mulaiA !== null || $mulaiB !== null) {
                $mulaiA = $mulaiA ?? 0;
                $mulaiB = $mulaiB ?? 0;
                if ($mulaiA !== $mulaiB) {
                    return $mulaiB <=> $mulaiA;
                }
            }

            $createdA = $a->created_at ? strtotime((string) $a->created_at) : 0;
            $createdB = $b->created_at ? strtotime((string) $b->created_at) : 0;
            if ($createdA !== $createdB) {
                return $createdB <=> $createdA;
            }

            return (int) $b->id <=> (int) $a->id;
        })->first();
    }

    protected function getLokasiPerusahaan(?\App\Models\RiwayatPekerjaan $job): ?object
    {
        if (!$job) {
            return null;
        }

        return $job->perusahaan?->lokasiAktif
            ?? $job->perusahaan?->lokasi->sortByDesc('id')->first();
    }

    protected function listFromRequest(Request $request, string $key): array
    {
        $value = $request->input($key);

        if (is_array($value)) {
            return array_values(array_filter(array_map('trim', $value), fn ($x) => $x !== ''));
        }

        $text = trim((string) $value);
        if ($text === '' || strtolower($text) === 'semua') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $text)), fn ($x) => $x !== ''));
    }

    public function index(Request $request)
    {
        $options = $this->getDashboardOptions();

        $initialFilters = [
            'angkatan' => $request->query('angkatan'),
            'tahun_lulus' => $request->query('tahun_lulus'),
            'jenis_kelamin' => $request->query('jenis_kelamin'),
            'status_alumni' => $request->query('status_alumni'),
            'bidang_pekerjaan' => $request->query('bidang_pekerjaan'),
            'wilayah' => $request->query('wilayah'),
        ];

        return view('admin.statistik.index', [
            'angkatanOptions' => $options['angkatanOptions'],
            'tahunLulusOptions' => $options['tahunLulusOptions'],
            'jenisKelaminOptions' => $options['jenisKelaminOptions'],
            'bidangOptions' => $options['bidangOptions'],
            'wilayahOptions' => $options['wilayahOptions'],
            'initialFilters' => $initialFilters,
        ]);
    }

    protected function getDashboardOptions(): array
    {
        $angkatanOptions = AlumniAkademik::query()
            ->select('angkatan')
            ->whereNotNull('angkatan')
            ->distinct()
            ->orderByDesc('angkatan')
            ->pluck('angkatan')
            ->values();

        $tahunLulusOptions = AlumniAkademik::query()
            ->select('tahun_lulus')
            ->whereNotNull('tahun_lulus')
            ->distinct()
            ->orderByDesc('tahun_lulus')
            ->pluck('tahun_lulus')
            ->values();

        $jenisKelaminOptions = Alumni::query()
            ->select('jenis_kelamin')
            ->whereNotNull('jenis_kelamin')
            ->distinct()
            ->orderBy('jenis_kelamin')
            ->pluck('jenis_kelamin')
            ->values();

        $bidangOptions = RiwayatPekerjaan::query()
            ->select('bidang_pekerjaan')
            ->whereNotNull('bidang_pekerjaan')
            ->distinct()
            ->orderBy('bidang_pekerjaan')
            ->pluck('bidang_pekerjaan')
            ->map(fn ($x) => trim((string) $x))
            ->filter(fn ($x) => $x !== '')
            ->values();

        $wilayahOptions = LokasiPerusahaan::query()
            ->select('kota')
            ->whereNotNull('kota')
            ->distinct()
            ->orderBy('kota')
            ->pluck('kota')
            ->map(fn ($x) => trim((string) $x))
            ->filter(fn ($x) => $x !== '')
            ->values();

        return [
            'angkatanOptions' => $angkatanOptions,
            'tahunLulusOptions' => $tahunLulusOptions,
            'jenisKelaminOptions' => $jenisKelaminOptions,
            'bidangOptions' => $bidangOptions,
            'wilayahOptions' => $wilayahOptions,
        ];
    }

    public function data(Request $request)
    {
        $angkatan = $this->listFromRequest($request, 'angkatan');
        $tahunLulus = $this->listFromRequest($request, 'tahun_lulus');
        $jenisKelamin = $this->listFromRequest($request, 'jenis_kelamin');
        $statusAlumni = $this->listFromRequest($request, 'status_alumni');
        $bidangPekerjaan = $this->listFromRequest($request, 'bidang_pekerjaan');
        $wilayah = $this->listFromRequest($request, 'wilayah');

        $query = Alumni::query()
            ->with([
                'akademik',
                'pekerjaan.perusahaan.lokasiAktif',
                'pekerjaan.perusahaan.lokasi',
                'studiLanjut',
            ]);

        if (!empty($jenisKelamin)) {
            $query->whereIn('jenis_kelamin', $jenisKelamin);
        }

        if (!empty($angkatan)) {
            $query->whereHas('akademik', function ($q) use ($angkatan) {
                $q->whereIn('angkatan', $angkatan);
            });
        }

        if (!empty($tahunLulus)) {
            $query->whereHas('akademik', function ($q) use ($tahunLulus) {
                $q->whereIn('tahun_lulus', $tahunLulus);
            });
        }

        $alumniRows = $query->get();

        $filtered = $alumniRows->filter(function ($alumni) use ($statusAlumni, $bidangPekerjaan, $wilayah) {
            $jobs = $alumni->pekerjaan ?? collect();
            $jobUtama = $this->pilihPekerjaanUtama($jobs);

            $hasStudi = ($alumni->studiLanjut && $alumni->studiLanjut->isNotEmpty());

            $workingAktif = $jobs->filter(function ($job) {
                $status = strtolower(trim((string) ($job->status_kerja ?? '')));
                if (!($status === 'bekerja' || $status === 'wirausaha')) {
                    return false;
                }
                return $this->getStatusKarirLower($job->status_karir) === 'utama' || (bool) $job->is_current;
            });

            $isBekerja = $workingAktif->isNotEmpty();

            $statusDerived = $hasStudi ? 'studi_lanjut' : ($isBekerja ? 'bekerja' : 'belum_bekerja');

            if (!empty($statusAlumni) && !in_array($statusDerived, $statusAlumni, true)) {
                return false;
            }

            if (!empty($bidangPekerjaan)) {
                $jobBidang = $workingAktif->isNotEmpty()
                    ? $this->pilihPekerjaanUtama($workingAktif)
                    : $jobUtama;

                $bidang = trim((string) ($jobBidang?->bidang_pekerjaan ?? ''));
                $bidang = $bidang !== '' ? $bidang : 'Tidak diketahui';
                if (!in_array($bidang, $bidangPekerjaan, true)) {
                    return false;
                }
            }

            if (!empty($wilayah)) {
                $jobWilayah = $workingAktif->isNotEmpty()
                    ? $this->pilihPekerjaanUtama($workingAktif)
                    : $jobUtama;

                $lokasi = $this->getLokasiPerusahaan($jobWilayah);
                $kota = trim((string) ($lokasi?->kota ?? ''));
                $provinsi = trim((string) ($lokasi?->provinsi ?? ''));
                $wil = $kota !== '' ? $kota : ($provinsi !== '' ? $provinsi : 'Tidak diketahui');
                if (!in_array($wil, $wilayah, true)) {
                    return false;
                }
            }

            return true;
        })->values();

        $totalAlumni = $filtered->count();

        $countBekerja = 0;
        $countBelum = 0;
        $countStudi = 0;
        $countMultiJob = 0;

        $masaTungguValues = [];

        $statusBuckets = [
            'Bekerja' => 0,
            'Belum Bekerja' => 0,
            'Studi Lanjut' => 0,
            'Wirausaha' => 0,
        ];

        $linearitasBuckets = [
            'Sangat Erat' => 0,
            'Erat' => 0,
            'Cukup Erat' => 0,
            'Kurang Erat' => 0,
            'Tidak Erat' => 0,
            'Tidak diketahui' => 0,
        ];

        $bidangCounts = [];
        $wilayahCounts = [];

        $masaTungguBuckets = [
            '0–3 bulan' => 0,
            '4–6 bulan' => 0,
            '7–12 bulan' => 0,
            '>12 bulan' => 0,
            'Tidak diketahui' => 0,
        ];

        $studiJenjangCounts = [
            'S2' => 0,
            'S3' => 0,
            'PPG' => 0,
            'Profesi' => 0,
            'Sertifikasi' => 0,
            'Lainnya' => 0,
        ];

        $kampusCounts = [];

        $trenTotal = [];
        $trenBekerja = [];
        $trenBelum = [];

        foreach ($filtered as $alumni) {
            $jobs = $alumni->pekerjaan ?? collect();
            $jobUtama = $this->pilihPekerjaanUtama($jobs);

            $workingAktif = $jobs->filter(function ($job) {
                $status = strtolower(trim((string) ($job->status_kerja ?? '')));
                if (!($status === 'bekerja' || $status === 'wirausaha')) {
                    return false;
                }
                return $this->getStatusKarirLower($job->status_karir) === 'utama' || (bool) $job->is_current;
            });

            $jobUtamaAktif = $workingAktif->isNotEmpty()
                ? $this->pilihPekerjaanUtama($workingAktif)
                : null;

            $hasStudi = ($alumni->studiLanjut && $alumni->studiLanjut->isNotEmpty());
            if ($hasStudi) {
                $countStudi += 1;
            }

            $isMultiJob = $jobs->count() > 1;
            if ($isMultiJob) {
                $countMultiJob += 1;
            }

            $statusKerja = strtolower(trim((string) ($jobUtamaAktif?->status_kerja ?? '')));
            $isBekerja = $jobUtamaAktif !== null && ($statusKerja === 'bekerja' || $statusKerja === 'wirausaha');

            if ($isBekerja) {
                $countBekerja += 1;
            } else {
                $countBelum += 1;
            }

            // Status chart (mutual exclusive untuk doughnut)
            if ($hasStudi) {
                $statusBuckets['Studi Lanjut'] += 1;
            } elseif ($statusKerja === 'wirausaha') {
                $statusBuckets['Wirausaha'] += 1;
            } elseif ($statusKerja === 'bekerja') {
                $statusBuckets['Bekerja'] += 1;
            } else {
                $statusBuckets['Belum Bekerja'] += 1;
            }

            // Chart berbasis pekerjaan: hitung hanya jika punya pekerjaan aktif/utama
            if ($jobUtamaAktif) {
                // Linearitas (dari perusahaan pekerjaan utama)
                $lin = trim((string) ($jobUtamaAktif->perusahaan?->linearitas ?? ''));
                $lin = $lin !== '' ? ucwords(strtolower($lin)) : 'Tidak diketahui';
                if (!array_key_exists($lin, $linearitasBuckets)) {
                    $lin = 'Tidak diketahui';
                }
                $linearitasBuckets[$lin] += 1;

                // Top bidang pekerjaan (dari pekerjaan utama)
                $bidang = trim((string) ($jobUtamaAktif->bidang_pekerjaan ?? ''));
                $bidang = $bidang !== '' ? $bidang : 'Tidak diketahui';
                $bidangCounts[$bidang] = ($bidangCounts[$bidang] ?? 0) + 1;

                // Top wilayah kerja (dari lokasi perusahaan)
                $lokasi = $this->getLokasiPerusahaan($jobUtamaAktif);
                $kota = trim((string) ($lokasi?->kota ?? ''));
                $provinsi = trim((string) ($lokasi?->provinsi ?? ''));
                $wil = $kota !== '' ? $kota : ($provinsi !== '' ? $provinsi : 'Tidak diketahui');
                $wilayahCounts[$wil] = ($wilayahCounts[$wil] ?? 0) + 1;

                // Masa tunggu (bulan)
                $masaTunggu = $jobUtamaAktif->masa_tunggu;
                $masaTungguNum = is_numeric($masaTunggu) ? (float) $masaTunggu : null;
                if ($masaTungguNum === null || $masaTungguNum < 0) {
                    $masaTungguBuckets['Tidak diketahui'] += 1;
                } elseif ($masaTungguNum <= 3) {
                    $masaTungguBuckets['0–3 bulan'] += 1;
                    $masaTungguValues[] = $masaTungguNum;
                } elseif ($masaTungguNum <= 6) {
                    $masaTungguBuckets['4–6 bulan'] += 1;
                    $masaTungguValues[] = $masaTungguNum;
                } elseif ($masaTungguNum <= 12) {
                    $masaTungguBuckets['7–12 bulan'] += 1;
                    $masaTungguValues[] = $masaTungguNum;
                } else {
                    $masaTungguBuckets['>12 bulan'] += 1;
                    $masaTungguValues[] = $masaTungguNum;
                }
            }

            // Studi lanjut (pilih record terbaru per alumni)
            $studiRow = null;
            if ($alumni->studiLanjut && $alumni->studiLanjut->isNotEmpty()) {
                $studiRow = $alumni->studiLanjut->sort(function ($a, $b) {
                    $rankA = (int) ($a->tahun_masuk ?? 0);
                    $rankB = (int) ($b->tahun_masuk ?? 0);
                    if ($rankA !== $rankB) {
                        return $rankB <=> $rankA;
                    }
                    $createdA = $a->created_at ? strtotime((string) $a->created_at) : 0;
                    $createdB = $b->created_at ? strtotime((string) $b->created_at) : 0;
                    if ($createdA !== $createdB) {
                        return $createdB <=> $createdA;
                    }
                    return (int) $b->id <=> (int) $a->id;
                })->first();
            }

            if ($studiRow) {
                $jenjangRaw = strtoupper(trim((string) ($studiRow->jenjang ?? '')));
                $jenjang = match (true) {
                    $jenjangRaw === 'S2' => 'S2',
                    $jenjangRaw === 'S3' => 'S3',
                    $jenjangRaw === 'PPG' => 'PPG',
                    str_contains($jenjangRaw, 'PROF') => 'Profesi',
                    str_contains($jenjangRaw, 'SERT') => 'Sertifikasi',
                    default => 'Lainnya'
                };
                $studiJenjangCounts[$jenjang] += 1;

                $kampus = trim((string) ($studiRow->kampus ?? ''));
                $kampus = $kampus !== '' ? $kampus : 'Tidak diketahui';
                $kampusCounts[$kampus] = ($kampusCounts[$kampus] ?? 0) + 1;
            }

            $angk = $alumni->akademik?->angkatan;
            $angkKey = $angk !== null ? (string) $angk : 'Tidak diketahui';

            $trenTotal[$angkKey] = ($trenTotal[$angkKey] ?? 0) + 1;
            if ($isBekerja) {
                $trenBekerja[$angkKey] = ($trenBekerja[$angkKey] ?? 0) + 1;
            } else {
                $trenBelum[$angkKey] = ($trenBelum[$angkKey] ?? 0) + 1;
            }
        }

        // Top 5 helpers
        arsort($bidangCounts);
        $topBidang = array_slice($bidangCounts, 0, 5, true);

        arsort($wilayahCounts);
        $topWilayah = array_slice($wilayahCounts, 0, 5, true);

        arsort($kampusCounts);
        $topKampus = array_slice($kampusCounts, 0, 5, true);

        // Sort trend keys: numeric desc for angkatan
        $trendKeys = array_keys($trenTotal);
        usort($trendKeys, function ($a, $b) {
            $na = is_numeric($a) ? (int) $a : null;
            $nb = is_numeric($b) ? (int) $b : null;
            if ($na !== null && $nb !== null) {
                return $na <=> $nb;
            }
            if ($na !== null) return -1;
            if ($nb !== null) return 1;
            return strcmp($a, $b);
        });

        $trendLabels = $trendKeys;
        $trendTotalSeries = array_map(fn ($k) => (int) ($trenTotal[$k] ?? 0), $trendKeys);
        $trendBekerjaSeries = array_map(fn ($k) => (int) ($trenBekerja[$k] ?? 0), $trendKeys);
        $trendBelumSeries = array_map(fn ($k) => (int) ($trenBelum[$k] ?? 0), $trendKeys);

        $avgMasaTunggu = null;
        if (count($masaTungguValues) > 0) {
            $avgMasaTunggu = array_sum($masaTungguValues) / count($masaTungguValues);
        }

        return response()->json([
            'filters' => [
                'angkatan' => $angkatan,
                'tahun_lulus' => $tahunLulus,
                'jenis_kelamin' => $jenisKelamin,
                'status_alumni' => $statusAlumni,
                'bidang_pekerjaan' => $bidangPekerjaan,
                'wilayah' => $wilayah,
            ],
            'kpis' => [
                'total_alumni' => $totalAlumni,
                'bekerja' => $countBekerja,
                'belum_bekerja' => $countBelum,
                'studi_lanjut' => $countStudi,
                'multi_job' => $countMultiJob,
                'rata_masa_tunggu' => $avgMasaTunggu,
            ],
            'charts' => [
                'status' => [
                    'labels' => array_values(array_keys($statusBuckets)),
                    'data' => array_values(array_map('intval', array_values($statusBuckets))),
                ],
                'linearitas' => [
                    'labels' => array_values(array_keys($linearitasBuckets)),
                    'data' => array_values(array_map('intval', array_values($linearitasBuckets))),
                ],
                'top_bidang' => [
                    'labels' => array_values(array_keys($topBidang)),
                    'data' => array_values(array_map('intval', array_values($topBidang))),
                ],
                'top_wilayah' => [
                    'labels' => array_values(array_keys($topWilayah)),
                    'data' => array_values(array_map('intval', array_values($topWilayah))),
                ],
                'masa_tunggu' => [
                    'labels' => array_values(array_keys($masaTungguBuckets)),
                    'data' => array_values(array_map('intval', array_values($masaTungguBuckets))),
                ],
                'studi_jenjang' => [
                    'labels' => array_values(array_keys($studiJenjangCounts)),
                    'data' => array_values(array_map('intval', array_values($studiJenjangCounts))),
                ],
                'top_kampus' => [
                    'labels' => array_values(array_keys($topKampus)),
                    'data' => array_values(array_map('intval', array_values($topKampus))),
                ],
                'tren_angkatan' => [
                    'labels' => $trendLabels,
                    'total' => $trendTotalSeries,
                    'bekerja' => $trendBekerjaSeries,
                    'belum_bekerja' => $trendBelumSeries,
                ],
            ],
        ]);
    }
}
