<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\AlumniAkademik;
use App\Models\RiwayatPekerjaan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\StatistikAlumniExport;

class StatistikController extends Controller
{
    protected const KALSEL_BBOX = [
        'minLat' => -5.0,
        'maxLat' => -1.0,
        'minLng' => 113.0,
        'maxLng' => 117.5,
    ];

    protected function getStatusKarirLower(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    protected function parseNumeric($value): ?float
    {
        if ($value === null) {
            return null;
        }

        if (is_int($value) || is_float($value)) {
            return is_finite((float) $value) ? (float) $value : null;
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        // Keep only numeric-ish characters (tolerate commas)
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) {
            return null;
        }

        $n = (float) $s;
        return is_finite($n) ? $n : null;
    }

    protected function normalizeGender(?string $value): string
    {
        $s = strtolower(trim((string) $value));
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        if ($s === '' || $s === '-' || $s === 'null' || str_contains($s, 'tidak')) {
            return 'Tidak diketahui';
        }

        if (in_array($s, ['l', 'lk', 'laki-laki', 'laki laki', 'laki'], true) || str_contains($s, 'pria')) {
            return 'Laki-laki';
        }

        if (in_array($s, ['p', 'pr', 'perempuan'], true) || str_contains($s, 'wanita')) {
            return 'Perempuan';
        }

        return 'Tidak diketahui';
    }

    protected function normalizeCompanyName(?string $value): ?string
    {
        $s = trim((string) $value);
        $s = preg_replace('/\s+/', ' ', $s) ?? $s;
        if ($s === '' || $s === '-' || strtolower($s) === 'null') {
            return null;
        }

        $lower = strtolower($s);
        if ($lower === 'tidak diketahui' || $lower === 'unknown') {
            return null;
        }

        // If the string is all-upper or all-lower, make it nicer (light normalization only).
        if ($s === strtoupper($s) || $s === strtolower($s)) {
            $s = ucwords(strtolower($s));
        }

        return $s;
    }

    protected function isValidLatLng($lat, $lng): bool
    {
        $la = $this->parseNumeric($lat);
        $lo = $this->parseNumeric($lng);
        if ($la === null || $lo === null) return false;
        if ($la < -90 || $la > 90) return false;
        if ($lo < -180 || $lo > 180) return false;
        return true;
    }

    protected function isInsideKalselBBox(float $lat, float $lng): bool
    {
        return $lat >= self::KALSEL_BBOX['minLat']
            && $lat <= self::KALSEL_BBOX['maxLat']
            && $lng >= self::KALSEL_BBOX['minLng']
            && $lng <= self::KALSEL_BBOX['maxLng'];
    }

    protected function pushHeatPoint(array &$buckets, float $lat, float $lng): void
    {
        // Round to reduce duplicates & payload size
        $latKey = number_format($lat, 5, '.', '');
        $lngKey = number_format($lng, 5, '.', '');
        $key = $latKey . ',' . $lngKey;
        $buckets[$key] = ($buckets[$key] ?? 0) + 1;
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

        // Gunakan lokasiAktif saja — tracer study hanya mencatat lokasi kerja SAAT INI,
        // bukan lokasi historis perusahaan. Konsisten dengan filter wilayah_id di query SQL.
        return $job->perusahaan?->lokasiAktif;
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

    protected function statistikFiltersFromRequest(Request $request): array
    {
        $wilayahId = $request->input('wilayah_id');
        $wilayahId = is_numeric($wilayahId) && (int) $wilayahId > 0 ? (int) $wilayahId : null;

        return [
            'angkatan' => $this->listFromRequest($request, 'angkatan'),
            'tahun_lulus' => $this->listFromRequest($request, 'tahun_lulus'),
            'jenis_kelamin' => $this->listFromRequest($request, 'jenis_kelamin'),
            'status_alumni' => $this->listFromRequest($request, 'status_alumni'),
            'bidang_pekerjaan' => $this->listFromRequest($request, 'bidang_pekerjaan'),
            'wilayah_id' => $wilayahId,
        ];
    }

    protected function buildFilteredAlumniQuery(array $filters)
    {
        $query = Alumni::query()
            ->with([
                'akademik',
                'alamat',
                // Muat lokasiAktif saja - tidak perlu semua rekaman lokasi historis perusahaan.
                // Semantik konsisten: persebaran wilayah kerja berdasarkan lokasi SAAT INI.
                'pekerjaan.perusahaan.lokasiAktif',
                'studiLanjut',
            ]);

        if (!empty($filters['jenis_kelamin'])) {
            $query->whereIn('jenis_kelamin', $filters['jenis_kelamin']);
        }

        if (!empty($filters['angkatan'])) {
            $query->whereHas('akademik', function ($q) use ($filters) {
                $q->whereIn('angkatan', $filters['angkatan']);
            });
        }

        if (!empty($filters['tahun_lulus'])) {
            $query->whereHas('akademik', function ($q) use ($filters) {
                $q->whereIn('tahun_lulus', $filters['tahun_lulus']);
            });
        }

        $this->applyWilayahConnectionFilter($query, $filters['wilayah_id'] ?? null);

        return $query;
    }

    protected function applyWilayahConnectionFilter($query, ?int $wilayahId): void
    {
        if ($wilayahId === null) {
            return;
        }

        // Alumni terhubung dengan wilayah jika bekerja saat ini DI wilayah itu
        // atau berdomisili DI wilayah itu.
        $query->where(function ($q) use ($wilayahId) {
            $q->whereHas('pekerjaan', function ($q2) use ($wilayahId) {
                $q2->where('is_current', true)
                    ->whereHas('perusahaan.lokasiAktif', function ($q3) use ($wilayahId) {
                        $q3->whereRaw(
                            'ST_Within(lokasi_perusahaan.geom::geometry, (SELECT geom FROM wilayah_kalsel WHERE id = ?))',
                            [$wilayahId]
                        );
                    });
            })
            ->orWhereHas('alamat', function ($q2) use ($wilayahId) {
                $q2->whereRaw(
                    'ST_Within(alamat_alumni.geom::geometry, (SELECT geom FROM wilayah_kalsel WHERE id = ?))',
                    [$wilayahId]
                );
            });
        });
    }

    protected function filterAlumniForStatistik($alumniRows, array $filters)
    {
        $statusAlumni = $filters['status_alumni'] ?? [];
        $bidangPekerjaan = $filters['bidang_pekerjaan'] ?? [];

        return $alumniRows->filter(function ($alumni) use ($statusAlumni, $bidangPekerjaan) {
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

            // wilayah_id sudah difilter di level SQL via ST_Within - tidak perlu filter PHP di sini.

            return true;
        })->values();
    }

    protected function resolveWilayahLabel(?int $wilayahId): ?string
    {
        if ($wilayahId === null) {
            return null;
        }

        $wilayahRow = DB::table('wilayah_kalsel')->where('id', $wilayahId)->first(['nama', 'level']);
        if (!$wilayahRow) {
            return 'ID: ' . $wilayahId;
        }

        return $wilayahRow->level === 'kota'
            ? 'Kota ' . $wilayahRow->nama
            : 'Kab. ' . $wilayahRow->nama;
    }

    protected function buildTopWilayahSubtitle(?string $wilayahLabel): string
    {
        if ($wilayahLabel === null) {
            return 'Distribusi wilayah kerja seluruh alumni yang bekerja';
        }

        return 'Wilayah kerja alumni terkait ' . $wilayahLabel
            . ' (bekerja atau berdomisili di ' . $wilayahLabel . ')';
    }

    protected function buildTopWilayahCounts(array $alumniIds, int $limit = 5): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $alumniIds), fn ($id) => $id > 0)));
        if (empty($ids)) {
            return [];
        }

        $limit = max(1, min(50, (int) $limit));
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $rows = DB::select(
            "
            SELECT work_wilayah.wilayah, COUNT(DISTINCT work_wilayah.alumni_id) AS total
            FROM (
                SELECT
                    ranked_jobs.alumni_id,
                    COALESCE(
                        MAX(
                            CASE
                                WHEN w.level = 'kota' THEN 'Kota ' || w.nama
                                WHEN w.nama IS NOT NULL THEN 'Kab. ' || w.nama
                                ELSE NULL
                            END
                        ),
                        'Luar Kalsel'
                    ) AS wilayah
                FROM (
                    SELECT
                        rp.alumni_id,
                        rp.perusahaan_id,
                        ROW_NUMBER() OVER (
                            PARTITION BY rp.alumni_id
                            ORDER BY
                                CASE WHEN LOWER(TRIM(COALESCE(rp.status_karir, ''))) = 'utama' THEN 0 ELSE 1 END,
                                COALESCE(rp.tanggal_mulai, DATE '0001-01-01') DESC,
                                COALESCE(rp.created_at, TIMESTAMP '0001-01-01 00:00:00') DESC,
                                rp.id DESC
                        ) AS rn
                    FROM riwayat_pekerjaan rp
                    WHERE rp.alumni_id IN ($placeholders)
                      AND rp.is_current IS TRUE
                      AND LOWER(TRIM(COALESCE(rp.status_kerja, ''))) IN ('bekerja', 'wirausaha')
                ) ranked_jobs
                LEFT JOIN (
                    SELECT perusahaan_id, MAX(id) AS lokasi_id
                    FROM lokasi_perusahaan
                    GROUP BY perusahaan_id
                ) lokasi_aktif ON lokasi_aktif.perusahaan_id = ranked_jobs.perusahaan_id
                LEFT JOIN lokasi_perusahaan lp ON lp.id = lokasi_aktif.lokasi_id
                LEFT JOIN wilayah_kalsel w ON lp.geom IS NOT NULL AND ST_Within(lp.geom::geometry, w.geom)
                WHERE ranked_jobs.rn = 1
                GROUP BY ranked_jobs.alumni_id
            ) work_wilayah
            GROUP BY work_wilayah.wilayah
            ORDER BY total DESC, work_wilayah.wilayah ASC
            LIMIT {$limit}
            ",
            $ids
        );

        $counts = [];
        foreach ($rows as $row) {
            $label = trim((string) ($row->wilayah ?? ''));
            $label = $label !== '' ? $label : 'Luar Kalsel';
            $counts[$label] = (int) ($row->total ?? 0);
        }

        return $counts;
    }

    public function index(Request $request)
    {
        $options = $this->getDashboardOptions();

        $wilayahIdRaw = $request->query('wilayah_id');
        $initialFilters = [
            'angkatan' => $request->query('angkatan'),
            'tahun_lulus' => $request->query('tahun_lulus'),
            'jenis_kelamin' => $request->query('jenis_kelamin'),
            'status_alumni' => $request->query('status_alumni'),
            'bidang_pekerjaan' => $request->query('bidang_pekerjaan'),
            'wilayah_id' => is_numeric($wilayahIdRaw) && (int) $wilayahIdRaw > 0 ? (int) $wilayahIdRaw : null,
        ];

        return view('admin.statistik.index', [
            'angkatanOptions' => $options['angkatanOptions'],
            'tahunLulusOptions' => $options['tahunLulusOptions'],
            'jenisKelaminOptions' => $options['jenisKelaminOptions'],
            'bidangOptions' => $options['bidangOptions'],
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

        return [
            'angkatanOptions' => $angkatanOptions,
            'tahunLulusOptions' => $tahunLulusOptions,
            'jenisKelaminOptions' => $jenisKelaminOptions,
            'bidangOptions' => $bidangOptions,
        ];
    }

    public function data(Request $request)
    {
        $filters = $this->statistikFiltersFromRequest($request);
        $wilayahId = $filters['wilayah_id'];
        $wilayahLabel = $this->resolveWilayahLabel($wilayahId);
        $topWilayahSubtitle = $this->buildTopWilayahSubtitle($wilayahLabel);

        $alumniRows = $this->buildFilteredAlumniQuery($filters)->get();

        $filtered = $this->filterAlumniForStatistik($alumniRows, $filters);

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
        $companyCounts = [];

        $genderBuckets = [
            'Laki-laki' => 0,
            'Perempuan' => 0,
            'Tidak diketahui' => 0,
        ];

        $toeflValidValues = [];
        $toeflBuckets = [
            '< 400' => 0,
            '400-449' => 0,
            '450-499' => 0,
            '>= 500' => 0,
            'Tidak diketahui' => 0,
        ];

        $salaryBuckets = [
            '< Rp1 juta' => 0,
            'Rp1–3 juta' => 0,
            'Rp3–5 juta' => 0,
            'Rp5–10 juta' => 0,
            '> Rp10 juta' => 0,
            'Tidak diketahui' => 0,
        ];
        $salaryValid = 0;
        $salaryUnknown = 0;

        $domicileHeatBuckets = [];
        $workHeatBuckets = [];
        $domicileNoCoord = 0;
        $workNoCoord = 0;

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

                // Distribusi rentang gaji (dari pekerjaan utama aktif)
                $gajiRaw = $jobUtamaAktif->gaji_nominal ?? null;
                $gaji = is_numeric($gajiRaw) ? (int) $gajiRaw : null;
                if ($gaji === null || $gaji <= 0) {
                    $salaryBuckets['Tidak diketahui'] += 1;
                    $salaryUnknown += 1;
                } else {
                    $salaryValid += 1;
                    if ($gaji < 1000000) {
                        $salaryBuckets['< Rp1 juta'] += 1;
                    } elseif ($gaji < 3000000) {
                        $salaryBuckets['Rp1–3 juta'] += 1;
                    } elseif ($gaji < 5000000) {
                        $salaryBuckets['Rp3–5 juta'] += 1;
                    } elseif ($gaji <= 10000000) {
                        $salaryBuckets['Rp5–10 juta'] += 1;
                    } else {
                        $salaryBuckets['> Rp10 juta'] += 1;
                    }
                }

                // Top bidang pekerjaan (dari pekerjaan utama)
                $bidang = trim((string) ($jobUtamaAktif->bidang_pekerjaan ?? ''));
                $bidang = $bidang !== '' ? $bidang : 'Tidak diketahui';
                $bidangCounts[$bidang] = ($bidangCounts[$bidang] ?? 0) + 1;

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

            // Top perusahaan/instansi (berdasarkan pekerjaan utama aktif)
            $company = $this->normalizeCompanyName($jobUtamaAktif?->perusahaan?->nama_perusahaan);
            if ($company) {
                $companyCounts[$company] = ($companyCounts[$company] ?? 0) + 1;
            }

            // Jenis kelamin (normalisasi)
            $genderLabel = $this->normalizeGender($alumni->jenis_kelamin);
            $genderBuckets[$genderLabel] = ($genderBuckets[$genderLabel] ?? 0) + 1;

            // TOEFL (dari akademik)
            $rawToefl = $alumni->akademik?->nilai_toefl;
            $toefl = $this->parseNumeric($rawToefl);
            // Validasi range TOEFL PBT (umum): 200 - 677
            if ($toefl === null || $toefl < 200 || $toefl > 677) {
                $toeflBuckets['Tidak diketahui'] += 1;
            } else {
                $toeflValidValues[] = $toefl;
                if ($toefl < 400) $toeflBuckets['< 400'] += 1;
                elseif ($toefl <= 449) $toeflBuckets['400-449'] += 1;
                elseif ($toefl <= 499) $toeflBuckets['450-499'] += 1;
                else $toeflBuckets['>= 500'] += 1;
            }

            // Heatmap domisili (alamat current) - Kalsel only
            $alamat = $alumni->alamat;
            $latDom = $alamat?->latitude;
            $lngDom = $alamat?->longitude;
            if ($this->isValidLatLng($latDom, $lngDom)) {
                $latNum = (float) $this->parseNumeric($latDom);
                $lngNum = (float) $this->parseNumeric($lngDom);
                $prov = strtolower(trim((string) ($alamat?->provinsi ?? '')));
                $isKalsel = ($prov !== '' && str_contains($prov, 'kalimantan selatan')) || $this->isInsideKalselBBox($latNum, $lngNum);
                if ($isKalsel) {
                    $this->pushHeatPoint($domicileHeatBuckets, $latNum, $lngNum);
                }
            } else {
                $domicileNoCoord += 1;
            }

            // Heatmap lokasi kerja (lokasi perusahaan pekerjaan utama) - Kalsel only
            if ($jobUtamaAktif) {
                $lok = $this->getLokasiPerusahaan($jobUtamaAktif);
                $latKerja = $lok?->latitude;
                $lngKerja = $lok?->longitude;
                if ($this->isValidLatLng($latKerja, $lngKerja)) {
                    $latNum = (float) $this->parseNumeric($latKerja);
                    $lngNum = (float) $this->parseNumeric($lngKerja);
                    $prov = strtolower(trim((string) ($lok?->provinsi ?? '')));
                    $isKalsel = ($prov !== '' && str_contains($prov, 'kalimantan selatan')) || $this->isInsideKalselBBox($latNum, $lngNum);
                    if ($isKalsel) {
                        $this->pushHeatPoint($workHeatBuckets, $latNum, $lngNum);
                    }
                } else {
                    $workNoCoord += 1;
                }
            } else {
                $workNoCoord += 1;
            }

            // Jika tidak punya pekerjaan utama aktif, gaji dianggap tidak diketahui.
            if (!$jobUtamaAktif) {
                $salaryBuckets['Tidak diketahui'] += 1;
                $salaryUnknown += 1;
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

        $topWilayah = $this->buildTopWilayahCounts($filtered->pluck('id')->all(), 5);

        arsort($kampusCounts);
        $topKampus = array_slice($kampusCounts, 0, 5, true);

        arsort($companyCounts);
        $topCompanies = array_slice($companyCounts, 0, 10, true);

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

        $avgToefl = null;
        $toeflValidCount = count($toeflValidValues);
        if ($toeflValidCount > 0) {
            $avgToefl = array_sum($toeflValidValues) / $toeflValidCount;
        } else {
            // Jika tidak ada TOEFL valid, chart distribusi ditampilkan sebagai empty state.
            foreach (array_keys($toeflBuckets) as $k) {
                $toeflBuckets[$k] = 0;
            }
        }

        $domicilePoints = array_map(function ($key, $weight) {
            [$lat, $lng] = array_map('floatval', explode(',', $key));
            return ['lat' => $lat, 'lng' => $lng, 'weight' => (int) $weight];
        }, array_keys($domicileHeatBuckets), array_values($domicileHeatBuckets));

        $workPoints = array_map(function ($key, $weight) {
            [$lat, $lng] = array_map('floatval', explode(',', $key));
            return ['lat' => $lat, 'lng' => $lng, 'weight' => (int) $weight];
        }, array_keys($workHeatBuckets), array_values($workHeatBuckets));

        return response()->json([
            'filters' => [
                'angkatan' => $filters['angkatan'],
                'tahun_lulus' => $filters['tahun_lulus'],
                'jenis_kelamin' => $filters['jenis_kelamin'],
                'status_alumni' => $filters['status_alumni'],
                'bidang_pekerjaan' => $filters['bidang_pekerjaan'],
                'wilayah_id' => $wilayahId,
            ],
            'meta' => [
                'wilayah_filter_label' => $wilayahLabel,
                'top_wilayah_subtitle' => $topWilayahSubtitle,
            ],
            'kpis' => [
                'total_alumni' => $totalAlumni,
                'bekerja' => $countBekerja,
                'belum_bekerja' => $countBelum,
                'studi_lanjut' => $countStudi,
                'multi_job' => $countMultiJob,
                'rata_masa_tunggu' => $avgMasaTunggu,
                'rata_toefl' => $avgToefl,
                'toefl_valid_count' => $toeflValidCount,
            ],
            'charts' => [
                'status' => [
                    'labels' => array_values(array_keys($statusBuckets)),
                    'data' => array_values(array_map('intval', array_values($statusBuckets))),
                ],
                'gender' => [
                    'labels' => array_values(array_keys($genderBuckets)),
                    'data' => array_values(array_map('intval', array_values($genderBuckets))),
                ],
                'linearitas' => [
                    'labels' => array_values(array_keys($linearitasBuckets)),
                    'data' => array_values(array_map('intval', array_values($linearitasBuckets))),
                ],
                'toefl_dist' => [
                    // Keys dikirim sederhana untuk konsistensi frontend.
                    'keys' => array_values(array_keys($toeflBuckets)),
                    // Labels untuk tampilan (boleh pakai simbol).
                    'labels' => ['< 400', '400–449', '450–499', '≥ 500', 'Tidak diketahui'],
                    'data' => [
                        (int) ($toeflBuckets['< 400'] ?? 0),
                        (int) ($toeflBuckets['400-449'] ?? 0),
                        (int) ($toeflBuckets['450-499'] ?? 0),
                        (int) ($toeflBuckets['>= 500'] ?? 0),
                        (int) ($toeflBuckets['Tidak diketahui'] ?? 0),
                    ],
                    'valid_count' => $toeflValidCount,
                    'distribution' => array_map('intval', $toeflBuckets),
                ],
                'top_bidang' => [
                    'labels' => array_values(array_keys($topBidang)),
                    'data' => array_values(array_map('intval', array_values($topBidang))),
                ],
                'top_company' => [
                    'labels' => array_values(array_keys($topCompanies)),
                    'data' => array_values(array_map('intval', array_values($topCompanies))),
                    'base' => [
                        'bekerja' => $countBekerja,
                    ],
                ],
                'top_wilayah' => [
                    'labels' => array_values(array_keys($topWilayah)),
                    'data' => array_values(array_map('intval', array_values($topWilayah))),
                    'subtitle' => $topWilayahSubtitle,
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
                'salary_distribution' => [
                    'labels' => array_keys($salaryBuckets),
                    'data' => array_values(array_map('intval', array_values($salaryBuckets))),
                    'total_valid' => $salaryValid,
                    'total_unknown' => $salaryUnknown,
                ],
            ],
            'heatmaps' => [
                'domisili' => [
                    'points' => array_values($domicilePoints),
                    'meta' => [
                        'valid_points' => count($domicilePoints),
                        'no_coord' => $domicileNoCoord,
                    ],
                ],
                'lokasi_kerja' => [
                    'points' => array_values($workPoints),
                    'meta' => [
                        'valid_points' => count($workPoints),
                        'no_coord' => $workNoCoord,
                    ],
                ],
            ],
        ]);
    }

    protected function isUnknownLabelForReport($label): bool
    {
        if ($label === null) return true;
        $s = trim((string) $label);
        if ($s === '' || $s === '-') return true;

        $lower = Str::of($s)->lower()->replaceMatches('/\s+/', ' ')->toString();
        $lower = str_replace(['–', '—'], '-', $lower);

        if (in_array($lower, ['null', 'n/a', 'na', 'none', 'unknown'], true)) return true;
        if (str_contains($lower, 'tidak diketahui')) return true;
        if (str_contains($lower, 'belum diketahui')) return true;
        if (str_contains($lower, 'belum diisi')) return true;
        if (str_contains($lower, 'kosong')) return true;

        return false;
    }

    protected function filterUnknownForReport(array $labels, array $data, bool $showUnknown): array
    {
        $len = min(count($labels), count($data));
        if ($showUnknown) {
            return [
                'labels' => array_slice($labels, 0, $len),
                'data' => array_slice($data, 0, $len),
            ];
        }

        $outLabels = [];
        $outData = [];
        for ($i = 0; $i < $len; $i++) {
            if ($this->isUnknownLabelForReport($labels[$i])) continue;
            $outLabels[] = $labels[$i];
            $outData[] = $data[$i];
        }

        return ['labels' => $outLabels, 'data' => $outData];
    }

    protected function buildInsightsForReport(array $payload, bool $showUnknown): array
    {
        $k = (array) ($payload['kpis'] ?? []);
        $c = (array) ($payload['charts'] ?? []);
        $meta = (array) ($payload['meta'] ?? []);
        $insights = [];

        $total = (int) ($k['total_alumni'] ?? 0);
        $bekerja = (int) ($k['bekerja'] ?? 0);
        $belum = (int) ($k['belum_bekerja'] ?? 0);
        $studi = (int) ($k['studi_lanjut'] ?? 0);

        if ($total > 0) {
            $pctBekerja = (int) round(($bekerja / $total) * 100);
            $pctBelum = (int) round(($belum / $total) * 100);
            $pctStudi = (int) round(($studi / $total) * 100);
            if ($pctBekerja >= 50) $insights[] = 'Mayoritas alumni sudah bekerja.';
            elseif ($pctBelum >= 50) $insights[] = 'Mayoritas alumni belum bekerja.';
            elseif ($pctStudi >= 50) $insights[] = 'Mayoritas alumni sedang studi lanjut.';
        }

        $pickTop = function ($labels, $data) use ($showUnknown) {
            $labels = is_array($labels) ? $labels : [];
            $data = is_array($data) ? $data : [];
            $filtered = $this->filterUnknownForReport($labels, $data, $showUnknown);
            $bestIdx = null;
            $bestVal = -INF;
            foreach ($filtered['data'] as $i => $v) {
                $n = is_numeric($v) ? (float) $v : 0.0;
                if ($n > $bestVal) {
                    $bestVal = $n;
                    $bestIdx = $i;
                }
            }
            if ($bestIdx === null || $bestVal <= 0) return null;
            return [
                'label' => (string) ($filtered['labels'][$bestIdx] ?? ''),
                'value' => (int) $bestVal,
            ];
        };

        $topBidang = $pickTop($c['top_bidang']['labels'] ?? [], $c['top_bidang']['data'] ?? []);
        if (!empty($topBidang['label'])) $insights[] = 'Bidang pekerjaan terbanyak adalah ' . $topBidang['label'] . '.';

        $wilWLabels = array_values($c['top_wilayah']['labels'] ?? []);
        $wilWData   = array_values($c['top_wilayah']['data'] ?? []);
        $kalselWilLabels = [];
        $kalselWilData   = [];
        foreach ($wilWLabels as $i => $lbl) {
            if (strtolower(trim((string) $lbl)) !== 'luar kalsel') {
                $kalselWilLabels[] = $lbl;
                $kalselWilData[]   = $wilWData[$i] ?? 0;
            }
        }
        $topWilayah = $pickTop($kalselWilLabels, $kalselWilData);
        if (!empty($topWilayah['label'])) {
            $insights[] = 'Wilayah kerja terbanyak adalah ' . $topWilayah['label'] . '.';

            $filterWilayah = trim((string) ($meta['wilayah_filter_label'] ?? ''));
            if ($filterWilayah !== '') {
                $normalizeWilayah = fn ($value) => Str::of((string) $value)
                    ->lower()
                    ->replaceMatches('/\s+/', ' ')
                    ->trim()
                    ->toString();

                if ($normalizeWilayah($topWilayah['label']) === $normalizeWilayah($filterWilayah)) {
                    $insights[] = 'Mayoritas alumni terkait ' . $filterWilayah . ' memang bekerja di wilayah tersebut.';
                } else {
                    $insights[] = 'Catatan: sebagian alumni terkait ' . $filterWilayah
                        . ' bekerja di luar ' . $filterWilayah
                        . ', dengan konsentrasi terbesar di ' . $topWilayah['label'] . '.';
                }
            }
        }

        $masa = $k['rata_masa_tunggu'] ?? null;
        if (is_numeric($masa)) {
            $insights[] = 'Rata-rata masa tunggu kerja sekitar ' . number_format((float) $masa, 1, ',', '.') . ' bulan.';
        }

        $sd = (array) ($c['salary_distribution'] ?? []);
        $unknownSalary = (int) ($sd['total_unknown'] ?? 0);
        $validSalary = (int) ($sd['total_valid'] ?? 0);
        $salaryTotal = $unknownSalary + $validSalary;
        if ($salaryTotal > 0 && $unknownSalary > 0) {
            $pctUnknown = (int) round(($unknownSalary / $salaryTotal) * 100);
            if ($pctUnknown >= 30) {
                $insights[] = 'Data gaji masih perlu dilengkapi (tidak diketahui cukup tinggi).';
            }
        }

        return array_values(array_slice(array_filter($insights), 0, 6));
    }

    public function exportPdf(Request $request)
    {
        $dataMode = strtolower(trim((string) $request->query('data_mode', 'valid')));
        $showUnknown = $dataMode === 'all';

        $json = $this->data($request);
        $payload = method_exists($json, 'getData') ? (array) $json->getData(true) : [];

        $angkatan = $this->listFromRequest($request, 'angkatan');
        $tahunLulus = $this->listFromRequest($request, 'tahun_lulus');
        $jenisKelamin = $this->listFromRequest($request, 'jenis_kelamin');
        $statusAlumni = $this->listFromRequest($request, 'status_alumni');
        $bidangPekerjaan = $this->listFromRequest($request, 'bidang_pekerjaan');

        $wilayahId = $request->input('wilayah_id');
        $wilayahId = is_numeric($wilayahId) && (int) $wilayahId > 0 ? (int) $wilayahId : null;
        $wilayahLabel = $this->resolveWilayahLabel($wilayahId) ?? 'Semua';

        $filterRows = [
            ['Filter' => 'Angkatan', 'Nilai' => empty($angkatan) ? 'Semua' : implode(', ', $angkatan)],
            ['Filter' => 'Tahun Lulus', 'Nilai' => empty($tahunLulus) ? 'Semua' : implode(', ', $tahunLulus)],
            ['Filter' => 'Jenis Kelamin', 'Nilai' => empty($jenisKelamin) ? 'Semua' : implode(', ', $jenisKelamin)],
            ['Filter' => 'Status Alumni', 'Nilai' => empty($statusAlumni) ? 'Semua' : implode(', ', $statusAlumni)],
            ['Filter' => 'Bidang Pekerjaan', 'Nilai' => empty($bidangPekerjaan) ? 'Semua' : implode(', ', $bidangPekerjaan)],
            ['Filter' => 'Wilayah Kerja', 'Nilai' => $wilayahLabel],
            ['Filter' => 'Mode Data Statistik', 'Nilai' => $showUnknown ? 'Semua data' : 'Hanya data valid'],
        ];

        $insights = $this->buildInsightsForReport($payload, $showUnknown);

        $viewData = [
            'payload' => $payload,
            'filterRows' => $filterRows,
            'showUnknown' => $showUnknown,
            'printedAt' => now(),
            'printedBy' => auth()->user()?->name ?? 'Admin',
            'insights' => $insights,
        ];

        $html = view('admin.statistik.pdf', $viewData)->render();

        if (!class_exists(\Dompdf\Dompdf::class)) {
            abort(500, 'Dompdf belum terpasang. Install dependency: composer require dompdf/dompdf');
        }

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();

        // Footer nomor halaman (kanan bawah) + info cetak (kiri bawah)
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $canvas->page_text(36, 820, 'Dicetak: ' . ($viewData['printedAt'] ?? now())->format('d-m-Y H:i'), $font, 9, [100, 116, 139]);
        $canvas->page_text(450, 820, 'Halaman {PAGE_NUM} / {PAGE_COUNT}', $font, 9, [100, 116, 139]);

        $filename = 'laporan-statistik-alumni-' . now()->format('Ymd_His') . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportExcel(Request $request)
    {
        $dataMode = strtolower(trim((string) $request->query('data_mode', 'valid')));
        $showUnknown = $dataMode === 'all';

        $json = $this->data($request);
        $payload = method_exists($json, 'getData') ? (array) $json->getData(true) : [];

        $angkatan = $this->listFromRequest($request, 'angkatan');
        $tahunLulus = $this->listFromRequest($request, 'tahun_lulus');
        $jenisKelamin = $this->listFromRequest($request, 'jenis_kelamin');
        $statusAlumni = $this->listFromRequest($request, 'status_alumni');
        $bidangPekerjaan = $this->listFromRequest($request, 'bidang_pekerjaan');

        $wilayahId = $request->input('wilayah_id');
        $wilayahId = is_numeric($wilayahId) && (int) $wilayahId > 0 ? (int) $wilayahId : null;
        $wilayahLabel = $this->resolveWilayahLabel($wilayahId) ?? 'Semua';

        $filterRows = [
            ['Filter' => 'Angkatan', 'Nilai' => empty($angkatan) ? 'Semua' : implode(', ', $angkatan)],
            ['Filter' => 'Tahun Lulus', 'Nilai' => empty($tahunLulus) ? 'Semua' : implode(', ', $tahunLulus)],
            ['Filter' => 'Jenis Kelamin', 'Nilai' => empty($jenisKelamin) ? 'Semua' : implode(', ', $jenisKelamin)],
            ['Filter' => 'Status Alumni', 'Nilai' => empty($statusAlumni) ? 'Semua' : implode(', ', $statusAlumni)],
            ['Filter' => 'Bidang Pekerjaan', 'Nilai' => empty($bidangPekerjaan) ? 'Semua' : implode(', ', $bidangPekerjaan)],
            ['Filter' => 'Wilayah Kerja', 'Nilai' => $wilayahLabel],
            ['Filter' => 'Mode Data Statistik', 'Nilai' => $showUnknown ? 'Semua data' : 'Hanya data valid'],
        ];

        $insights = $this->buildInsightsForReport($payload, $showUnknown);

        $printedAt = now();
        $printedBy = auth()->user()?->name ?? 'Admin';

        $filters = [
            'angkatan' => $angkatan,
            'tahun_lulus' => $tahunLulus,
            'jenis_kelamin' => $jenisKelamin,
            'status_alumni' => $statusAlumni,
            'bidang_pekerjaan' => $bidangPekerjaan,
            'wilayah_id' => $wilayahId,
            'data_mode' => $showUnknown ? 'all' : 'valid',
        ];

        $alumniDetail = $this->buildFilteredAlumniForExport($request);

        $export = new StatistikAlumniExport(
            payload: $payload,
            filterRows: $filterRows,
            insights: $insights,
            printedAt: $printedAt,
            printedBy: $printedBy,
            showUnknown: $showUnknown,
            filters: $filters,
            alumniDetail: $alumniDetail
        );

        $filename = 'laporan-statistik-alumni-' . $printedAt->format('Ymd_His') . '.xlsx';

        return Excel::download($export, $filename);
    }

    protected function buildFilteredAlumniForExport(Request $request)
    {
        $filters = $this->statistikFiltersFromRequest($request);
        $alumniRows = $this->buildFilteredAlumniQuery($filters)->get();

        return $this->filterAlumniForStatistik($alumniRows, $filters);
    }
}
