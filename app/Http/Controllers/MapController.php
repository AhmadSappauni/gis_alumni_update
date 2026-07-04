<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use App\Models\RiwayatPekerjaan;
use App\Models\StudiLanjut;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class MapController extends Controller
{
    private array $wilayahKeywordIdCache = [];

    private function normalisasiWilayahKey(?string $value): string
    {
        $text = strtolower(trim((string) $value));

        $text = preg_replace('/\b(kab\.?|kabupaten|kota)\b/u', '', $text) ?? $text;
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        return trim($text);
    }

    private function getLokasiPerusahaan(?\App\Models\RiwayatPekerjaan $job): ?object
    {
        if (!$job) {
            return null;
        }

        return $job->perusahaan?->lokasiAktif;
    }

    private function getStatusKarirLower(?string $value): string
    {
        return strtolower(trim((string) $value));
    }

    private function buildMapPayload(?Request $request = null): array
    {
        $filters = $this->getMapFilters($request);
        $canViewBelumBekerja = $this->canViewBelumBekerja($request);
        $includeBekerja = $this->statusFilterIncludes($filters, 'bekerja');
        $includeBelumBekerja = $canViewBelumBekerja
            && $this->statusFilterIncludes($filters, 'belum_bekerja')
            && $this->canIncludeBelumBekerjaForFilters($filters);
        $includeStudiLanjut = $this->statusFilterIncludes($filters, 'studi_lanjut');
        $markers = collect();
        $studiLanjutMarkers = collect();
        $workingAlumniIds = [];

        /*
        |--------------------------------------------------------------------------
        | 1. ALUMNI YANG BEKERJA
        |--------------------------------------------------------------------------
        | Ambil pekerjaan aktif sekarang
        | Titik marker = lokasi perusahaan
        */

        $pekerja = $includeBekerja
            ? $this->workingJobsQuery($filters, true)->get()
            : collect();

        if ($includeBelumBekerja) {
            $pekerjaUntukEksklusi = $includeBekerja && !$this->hasWorkingMarkerSpecificFilters($filters)
                ? $pekerja
                : $this->workingJobsQuery($filters, false)->get();

            $workingAlumniIds = $this->collectWorkingAlumniIdsWithMarker($pekerjaUntukEksklusi);
        }

        $pekerjaGrouped = $pekerja->groupBy('alumni_id');
        $pekerjaPerAlumni = $this->primaryCurrentJobs($pekerja);

        foreach ($pekerjaPerAlumni as $job) {
            $markerLocation = $this->resolveWorkingMarkerLocation($job);

            if (!$markerLocation) {
                continue;
            }

            if ($job->alumni_id) {
                $workingAlumniIds[] = (int) $job->alumni_id;
            }

            $jobsAlumni = $pekerjaGrouped->get($job->alumni_id, collect());

            $pekerjaanSampingan = $jobsAlumni
                ->filter(function ($row) use ($job) {
                    return (int) $row->id !== (int) $job->id
                        && $this->getStatusKarirLower($row->status_karir) === 'sampingan';
                })
                ->values();

            $pekerjaanLainnya = $pekerjaanSampingan
                ->map(function ($row) {
                    $lokasi = $this->getLokasiPerusahaan($row);

                    if (!$lokasi || !$lokasi->latitude || !$lokasi->longitude) {
                        return null;
                    }

                    return [
                        'id' => $row->id,
                        'perusahaan' => $row->perusahaan?->nama_perusahaan,
                        'jabatan' => $row->jabatan,
                        'status_karir' => $row->status_karir,
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
                'tahun_lulus'   => $job->alumni?->akademik?->tahun_lulus,
                'angkatan'      => $job->alumni?->akademik?->angkatan,
                'judul_skripsi' => $job->alumni?->akademik?->judul_skripsi,

                'status'        => 'Bekerja',

                'latitude'      => (float) $markerLocation['latitude'],
                'longitude'     => (float) $markerLocation['longitude'],

                'kota'          => $markerLocation['kota'],
                'provinsi'      => $markerLocation['provinsi'],
                'wilayah_key'   => $this->normalisasiWilayahKey($markerLocation['kota'] ?? $markerLocation['provinsi']),
                'alamat'        => $markerLocation['alamat'],

                'perusahaan'    => $job->perusahaan?->nama_perusahaan,
                'jabatan'       => $job->jabatan,
                'bidang'        => $job->bidang_pekerjaan,
                'linearitas'    => $job->perusahaan?->linearitas,
                'link_linkedin' => $job->perusahaan?->link_linkedin,

                'pekerjaan_lainnya' => $pekerjaanLainnya,
                'multi_job_count' => $pekerjaanSampingan->count(),
                'multi_job_mappable_count' => $pekerjaanLainnya->count(),
            ]);
        }

        $workingAlumniIds = collect($workingAlumniIds)
            ->filter()
            ->unique()
            ->values()
            ->all();

        /*
        |--------------------------------------------------------------------------
        | 2. ALUMNI BELUM BEKERJA
        |--------------------------------------------------------------------------
        | Ambil alumni yang tidak punya pekerjaan aktif bekerja
        | Titik marker = domisili alumni
        */

        if ($includeBelumBekerja) {
            $belumKerjaQuery = Alumni::query()
                ->select(['id', 'nim', 'nama_lengkap'])
                ->with($this->alumniMarkerRelations())
                ->whereHas('alamat', function ($q) {
                    $q->whereNotNull('latitude')
                        ->whereNotNull('longitude');
                });

            if (!empty($workingAlumniIds)) {
                $belumKerjaQuery->whereNotIn('id', $workingAlumniIds);
            }

            $this->applyBelumBekerjaQueryFilters($belumKerjaQuery, $filters);

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
                    'tahun_lulus'   => $alumni->akademik?->tahun_lulus,
                    'angkatan'      => $alumni->akademik?->angkatan,
                    'judul_skripsi' => $alumni->akademik?->judul_skripsi,

                    'status'        => 'Belum Bekerja',

                    'latitude'      => (float) $alamat->latitude,
                    'longitude'     => (float) $alamat->longitude,

                    'kota'          => $alamat->kota,
                    'provinsi'      => $alamat->provinsi,
                    'wilayah_key'   => $this->normalisasiWilayahKey($alamat->kota ?? $alamat->provinsi),
                    'alamat'        => $alamat->alamat_lengkap,

                    'perusahaan'    => null,
                    'jabatan'       => null,
                    'bidang'        => null,
                    'linearitas'    => null,
                    'link_linkedin' => null,

                    'pekerjaan_lainnya' => [],
                ]);
            }
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

        if ($includeStudiLanjut) {
            $studiLanjutQuery = StudiLanjut::query()
                ->select([
                    'id',
                    'alumni_id',
                    'kampus',
                    'alamat_kampus',
                    'kota_kampus',
                    'provinsi_kampus',
                    'latitude',
                    'longitude',
                    'jenjang',
                    'program_studi',
                    'tahun_masuk',
                    'tahun_lulus',
                    'status',
                ])
                ->with([
                    'alumni' => function ($q) {
                        $q->select(['id', 'nim', 'nama_lengkap']);
                    },
                    'alumni.akademik' => function ($q) {
                        $q->select(['id', 'alumni_id', 'angkatan', 'tahun_lulus']);
                    },
                ])
                ->whereNotNull('latitude')
                ->whereNotNull('longitude');

            $this->applyStudiLanjutQueryFilters($studiLanjutQuery, $filters);

            $studiLanjutRows = $studiLanjutQuery->get();

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
                    'nim' => $alumni->nim,
                    'nama_lengkap' => $alumni->nama_lengkap,
                    'tahun_lulus_alumni' => $alumni->akademik?->tahun_lulus,
                    'angkatan' => $alumni->akademik?->angkatan,

                    'kampus' => $row->kampus,
                    'alamat_kampus' => $row->alamat_kampus,
                    'kota_kampus' => $row->kota_kampus,
                    'provinsi_kampus' => $row->provinsi_kampus,
                    'wilayah_key' => $this->normalisasiWilayahKey($row->kota_kampus ?? $row->provinsi_kampus),
                    'latitude' => (float) $row->latitude,
                    'longitude' => (float) $row->longitude,
                    'jenjang' => $row->jenjang,
                    'program_studi' => $row->program_studi,
                    'tahun_masuk' => $row->tahun_masuk,
                    'tahun_lulus_studi' => $row->tahun_lulus,
                    'status' => $row->status,
                ]);
            }
        }

        $markers = $markers
            ->filter(function ($item) use ($filters) {
                return $this->mainMarkerMatchesFilters($item, $filters);
            })
            ->values();

        $studiLanjutMarkers = $studiLanjutMarkers
            ->filter(function ($item) use ($filters) {
                return $this->studiLanjutMarkerMatchesFilters($item, $filters);
            })
            ->values();

        $belumCount = $markers
            ->where('status', 'Belum Bekerja')
            ->pluck('alumni_id')
            ->filter()
            ->unique()
            ->count();

        $workingSummaryIds = $includeBekerja
            ? $this->primaryCurrentJobs($pekerja)
                ->pluck('alumni_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
            : collect();

        $multiJobSummaryIds = $includeBekerja
            ? $this->collectMultiJobAlumniIds($pekerja)
            : collect();

        $studiLanjutSummaryIds = $includeStudiLanjut
            ? $this->collectStudiLanjutAlumniIds($filters)
            : collect();

        $multiJobCount = $multiJobSummaryIds->count();

        $studiCount = $studiLanjutSummaryIds->count();

        $workingCount = $workingSummaryIds->count();

        $totalAlumni = collect()
            ->merge($markers->pluck('alumni_id'))
            ->merge($studiLanjutMarkers->pluck('alumni_id'))
            ->merge($workingSummaryIds)
            ->merge($multiJobSummaryIds)
            ->merge($studiLanjutSummaryIds)
            ->filter()
            ->unique()
            ->count();

        $mapPayload = [
            'total_alumni' => $totalAlumni,
            'total_bekerja' => $workingCount,
            'total_belum_bekerja' => $belumCount,
            'total_multi_job' => $multiJobCount,
            'total_studi_lanjut' => $studiCount,
            'can_view_belum_bekerja' => $canViewBelumBekerja,
            'markers' => $markers,
            'studi_lanjut_markers' => $studiLanjutMarkers,
        ];

        return $mapPayload;
    }

    public function index()
    {
        $mapPayload = $this->emptyMapPayload();

        return view('index', [
            'dataPekerjaan' => collect(),
            'studiLanjutMarkers' => collect(),
            'mapSummary' => $this->summaryFromPayload($mapPayload),
            'mapPayload' => $mapPayload,
            'mapDataUrl' => route('map.data'),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return response()->json($this->buildMapPayload($request));
    }

    private function emptyMapPayload(): array
    {
        return [
            'total_alumni' => 0,
            'total_bekerja' => 0,
            'total_belum_bekerja' => 0,
            'total_multi_job' => 0,
            'total_studi_lanjut' => 0,
            'can_view_belum_bekerja' => $this->canViewBelumBekerja(),
            'markers' => [],
            'studi_lanjut_markers' => [],
        ];
    }

    private function canViewBelumBekerja(?Request $request = null): bool
    {
        $user = $request?->user() ?? auth()->user();

        return (bool) $user?->canViewSensitiveAlumniData();
    }

    private function summaryFromPayload(array $payload): array
    {
        return [
            'total_alumni' => (int) ($payload['total_alumni'] ?? 0),
            'total_bekerja' => (int) ($payload['total_bekerja'] ?? 0),
            'total_belum_bekerja' => (int) ($payload['total_belum_bekerja'] ?? 0),
            'total_multi_job' => (int) ($payload['total_multi_job'] ?? 0),
            'total_studi_lanjut' => (int) ($payload['total_studi_lanjut'] ?? 0),
        ];
    }

    private function alumniMarkerRelations(): array
    {
        return [
            'akademik' => function ($q) {
                $q->select(['id', 'alumni_id', 'angkatan', 'tahun_lulus', 'judul_skripsi']);
            },
            'alamat' => function ($q) {
                $q->select(['id', 'alumni_id', 'alamat_lengkap', 'kota', 'provinsi', 'latitude', 'longitude', 'is_current']);
            },
        ];
    }

    private function workingMarkerRelations(): array
    {
        return [
            'alumni' => function ($q) {
                $q->select(['id', 'nim', 'nama_lengkap']);
            },
            'alumni.akademik' => function ($q) {
                $q->select(['id', 'alumni_id', 'angkatan', 'tahun_lulus', 'judul_skripsi']);
            },
            'alumni.alamat' => function ($q) {
                $q->select(['id', 'alumni_id', 'alamat_lengkap', 'kota', 'provinsi', 'latitude', 'longitude', 'is_current']);
            },
            'perusahaan' => function ($q) {
                $q->select(['id', 'nama_perusahaan', 'linearitas', 'link_linkedin']);
            },
            'perusahaan.lokasiAktif' => function ($q) {
                $q->select([
                    'lokasi_perusahaan.id',
                    'lokasi_perusahaan.perusahaan_id',
                    'lokasi_perusahaan.alamat_lengkap',
                    'lokasi_perusahaan.kota',
                    'lokasi_perusahaan.provinsi',
                    'lokasi_perusahaan.latitude',
                    'lokasi_perusahaan.longitude',
                ]);
            },
        ];
    }

    private function workingJobsQuery(array $filters, bool $applyMarkerFilters)
    {
        $query = RiwayatPekerjaan::query()
            ->select([
                'id',
                'alumni_id',
                'perusahaan_id',
                'jabatan',
                'bidang_pekerjaan',
                'status_kerja',
                'is_current',
                'tanggal_mulai',
                'status_karir',
                'created_at',
            ])
            ->with($this->workingMarkerRelations())
            ->whereRaw('is_current IS TRUE')
            ->where(function ($q) {
                $q->whereNull('status_kerja')
                    ->orWhereIn('status_kerja', ['Bekerja', 'bekerja', 'BEKERJA', 'Kerja', 'kerja', 'KERJA']);
            });

        if ($applyMarkerFilters) {
            $this->applyWorkingQueryFilters($query, $filters);
        } else {
            $this->applyAcademicFiltersToRelation($query, 'alumni.akademik', $filters);
        }

        return $query;
    }

    private function primaryCurrentJobs(Collection $pekerja): Collection
    {
        return $pekerja
            ->groupBy('alumni_id')
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
            })
            ->values();
    }

    private function resolveWorkingMarkerLocation(?RiwayatPekerjaan $job): ?array
    {
        if (!$job) {
            return null;
        }

        $lokasi = $this->getLokasiPerusahaan($job);

        $markerLat = $lokasi?->latitude;
        $markerLng = $lokasi?->longitude;
        $markerKota = $lokasi?->kota;
        $markerProv = $lokasi?->provinsi;
        $markerAlamat = $lokasi?->alamat_lengkap;

        if (!$markerLat || !$markerLng) {
            $alamatAlumni = $job->alumni?->alamat;
            if ($alamatAlumni?->latitude && $alamatAlumni?->longitude) {
                $markerLat = $alamatAlumni->latitude;
                $markerLng = $alamatAlumni->longitude;
                $markerKota = $markerKota ?: $alamatAlumni->kota;
                $markerProv = $markerProv ?: $alamatAlumni->provinsi;
                $markerAlamat = $markerAlamat ?: $alamatAlumni->alamat_lengkap;
            }
        }

        if (!$markerLat || !$markerLng) {
            return null;
        }

        return [
            'latitude' => (float) $markerLat,
            'longitude' => (float) $markerLng,
            'kota' => $markerKota,
            'provinsi' => $markerProv,
            'alamat' => $markerAlamat,
        ];
    }

    private function collectWorkingAlumniIdsWithMarker(Collection $pekerja): array
    {
        return $this->primaryCurrentJobs($pekerja)
            ->filter(function ($job) {
                return $job->alumni_id && $this->resolveWorkingMarkerLocation($job);
            })
            ->pluck('alumni_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function collectMultiJobAlumniIds(Collection $pekerja): Collection
    {
        return $pekerja
            ->groupBy('alumni_id')
            ->filter(function ($jobs, $alumniId) {
                if (!$alumniId || $jobs->count() < 2) {
                    return false;
                }

                return $jobs->contains(function ($job) {
                    return $this->getStatusKarirLower($job->status_karir) === 'sampingan';
                });
            })
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function collectStudiLanjutAlumniIds(array $filters): Collection
    {
        $query = StudiLanjut::query()
            ->select(['alumni_id'])
            ->whereNotNull('alumni_id');

        $this->applyStudiLanjutQueryFilters($query, $filters);

        return $query
            ->pluck('alumni_id')
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();
    }

    private function getMapFilters(?Request $request): array
    {
        if (!$request) {
            return [
                'keyword' => '',
                'search_scopes' => [],
                'linearitas' => null,
                'bidang' => [],
                'statuses' => [],
                'tahun' => null,
                'angkatan' => null,
            ];
        }

        $keyword = strtolower(trim((string) $request->query('search', '')));
        if (strlen($keyword) < 2) {
            $keyword = '';
        }

        $tahun = $request->query('tahun');
        $tahun = is_numeric($tahun) && $tahun !== 'semua' ? (int) $tahun : null;

        $angkatan = trim((string) $request->query('angkatan', ''));
        if ($angkatan === '' || strtolower($angkatan) === 'semua') {
            $angkatan = null;
        }

        $linearitas = trim((string) $request->query('linearitas', ''));
        if ($linearitas === '' || strtolower($linearitas) === 'semua') {
            $linearitas = null;
        }

        $wilayahId = $request->query('wilayah_id');
        $wilayahId = is_numeric($wilayahId) && (int) $wilayahId > 0 ? (int) $wilayahId : null;

        return [
            'keyword' => $keyword,
            'search_scopes' => $this->parseFilterList($request, 'search_scope'),
            'linearitas' => $linearitas,
            'bidang' => $this->parseFilterList($request, 'bidang_pekerjaan'),
            'statuses' => $this->parseFilterList($request, 'status'),
            'tahun' => $tahun,
            'angkatan' => $angkatan,
            'wilayah_id' => $wilayahId,
        ];
    }

    private function parseFilterList(Request $request, string $key): array
    {
        $raw = $request->query($key, []);
        $items = is_array($raw) ? $raw : explode(',', (string) $raw);

        return collect($items)
            ->map(function ($item) {
                return trim((string) $item);
            })
            ->filter(function ($item) {
                return $item !== '' && strtolower($item) !== 'semua';
            })
            ->unique()
            ->values()
            ->all();
    }

    private function canIncludeBelumBekerjaForFilters(array $filters): bool
    {
        if (!empty($filters['bidang'] ?? [])) {
            return false;
        }

        $linearitas = $filters['linearitas'] ?? null;

        return $linearitas === null || $linearitas === 'Tidak Erat';
    }

    private function hasWorkingMarkerSpecificFilters(array $filters): bool
    {
        return ($filters['keyword'] ?? '') !== ''
            || !empty($filters['bidang'] ?? [])
            || ($filters['linearitas'] ?? null) !== null
            || ($filters['wilayah_id'] ?? null) !== null;
    }

    private function applyWorkingQueryFilters($query, array $filters): void
    {
        $this->applyAcademicFiltersToRelation($query, 'alumni.akademik', $filters);

        $bidangFilters = $filters['bidang'] ?? [];
        if (!empty($bidangFilters)) {
            $query->whereIn('bidang_pekerjaan', $bidangFilters);
        }

        $wilayahId = $filters['wilayah_id'] ?? null;
        if ($wilayahId !== null) {
            $query->whereHas('perusahaan.lokasiAktif', function ($q) use ($wilayahId) {
                $q->whereRaw(
                    'ST_Within(lokasi_perusahaan.geom::geometry, (SELECT geom FROM wilayah_kalsel WHERE id = ?))',
                    [$wilayahId]
                );
            });
        }

        $linearitas = $filters['linearitas'] ?? null;
        if ($linearitas !== null) {
            $query->where(function ($q) use ($linearitas) {
                if ($linearitas === 'Tidak Erat') {
                    $q->whereNull('perusahaan_id')
                        ->orWhereHas('perusahaan', function ($perusahaan) {
                            $perusahaan->whereNull('linearitas')
                                ->orWhere('linearitas', '')
                                ->orWhere('linearitas', 'Tidak Erat');
                        });

                    return;
                }

                $q->whereHas('perusahaan', function ($perusahaan) use ($linearitas) {
                    $perusahaan->where('linearitas', $linearitas);
                });
            });
        }

        $this->applyKeywordQueryFilter($query, $filters, [
            'nama' => function ($q, string $pattern) {
                $q->whereHas('alumni', function ($alumni) use ($pattern) {
                    $this->whereAnyLike($alumni, ['nama_lengkap'], $pattern);
                });
            },
            'nim' => function ($q, string $pattern) {
                $q->whereHas('alumni', function ($alumni) use ($pattern) {
                    $this->whereAnyLike($alumni, ['nim'], $pattern);
                });
            },
            'perusahaan' => function ($q, string $pattern) {
                $q->whereHas('perusahaan', function ($perusahaan) use ($pattern) {
                    $this->whereAnyLike($perusahaan, ['nama_perusahaan'], $pattern);
                });
            },
        ]);
    }

    private function applyBelumBekerjaQueryFilters($query, array $filters): void
    {
        $this->applyAcademicFiltersToRelation($query, 'akademik', $filters);

        $wilayahId = $filters['wilayah_id'] ?? null;
        if ($wilayahId !== null) {
            $query->whereHas('alamat', function ($q) use ($wilayahId) {
                $q->whereRaw(
                    'ST_Within(alamat_alumni.geom::geometry, (SELECT geom FROM wilayah_kalsel WHERE id = ?))',
                    [$wilayahId]
                );
            });
        }

        $this->applyKeywordQueryFilter($query, $filters, [
            'nama' => function ($q, string $pattern) {
                $this->whereAnyLike($q, ['nama_lengkap'], $pattern);
            },
            'nim' => function ($q, string $pattern) {
                $this->whereAnyLike($q, ['nim'], $pattern);
            },
        ]);
    }

    private function applyStudiLanjutQueryFilters($query, array $filters): void
    {
        $this->applyAcademicFiltersToRelation($query, 'alumni.akademik', $filters);

        $wilayahId = $filters['wilayah_id'] ?? null;
        if ($wilayahId !== null) {
            $this->wherePointWithinWilayahIds(
                $query,
                'studi_lanjut.geom::geometry',
                [$wilayahId]
            );
        }

        $this->applyKeywordQueryFilter($query, $filters, [
            'nama' => function ($q, string $pattern) {
                $q->whereHas('alumni', function ($alumni) use ($pattern) {
                    $this->whereAnyLike($alumni, ['nama_lengkap'], $pattern);
                });
            },
            'nim' => function ($q, string $pattern) {
                $q->whereHas('alumni', function ($alumni) use ($pattern) {
                    $this->whereAnyLike($alumni, ['nim'], $pattern);
                });
            },
            'perusahaan' => function ($q, string $pattern) {
                $this->whereAnyLike($q, ['kampus', 'jenjang', 'program_studi'], $pattern);
            },
        ]);
    }

    private function applyKeywordQueryFilter($query, array $filters, array $callbacks): void
    {
        $keyword = $filters['keyword'] ?? '';
        if ($keyword === '') {
            return;
        }

        $pattern = $this->likePattern($keyword);
        $scopes = $this->activeSearchScopes($filters);

        $query->where(function ($outer) use ($callbacks, $pattern, $scopes) {
            $hasCondition = false;

            foreach (['nama', 'nim', 'perusahaan'] as $scope) {
                if (!($scopes[$scope] ?? false) || !isset($callbacks[$scope])) {
                    continue;
                }

                $method = $hasCondition ? 'orWhere' : 'where';
                $outer->{$method}(function ($inner) use ($callbacks, $scope, $pattern) {
                    $callbacks[$scope]($inner, $pattern);
                });

                $hasCondition = true;
            }

            if (!$hasCondition) {
                $outer->whereRaw('1 = 0');
            }
        });
    }

    private function applyAcademicFiltersToRelation($query, string $relation, array $filters): void
    {
        $angkatan = $filters['angkatan'] ?? null;
        $tahunRange = $filters['tahun'] ?? null;

        if ($angkatan === null && $tahunRange === null) {
            return;
        }

        $query->whereHas($relation, function ($q) use ($angkatan, $tahunRange) {
            if ($angkatan !== null) {
                $q->where('angkatan', $angkatan);
            }

            if ($tahunRange !== null) {
                $currentYear = (int) date('Y');
                $q->whereBetween('tahun_lulus', [$currentYear - $tahunRange, $currentYear]);
            }
        });
    }

    private function whereAnyLike($query, array $columns, string $pattern): void
    {
        $query->where(function ($q) use ($columns, $pattern) {
            foreach ($columns as $index => $column) {
                $method = $index === 0 ? 'whereRaw' : 'orWhereRaw';
                $q->{$method}('LOWER(' . $column . ') LIKE ?', [$pattern]);
            }
        });
    }

    private function wherePointWithinWilayahIds($query, string $pointExpression, array $wilayahIds): void
    {
        $wilayahIds = array_values(array_unique(array_map('intval', $wilayahIds)));
        $wilayahIds = array_values(array_filter($wilayahIds, fn (int $id) => $id > 0));

        if (empty($wilayahIds)) {
            $query->whereRaw('1 = 0');
            return;
        }

        $placeholders = implode(', ', array_fill(0, count($wilayahIds), '?'));

        $query->whereRaw(
            "EXISTS (
                SELECT 1
                FROM wilayah_kalsel search_wilayah
                WHERE search_wilayah.id IN ({$placeholders})
                  AND ST_Within({$pointExpression}, search_wilayah.geom)
            )",
            $wilayahIds
        );
    }

    private function resolveWilayahIdsFromKeyword(string $keyword): array
    {
        $normalizedKeyword = $this->normalizeRegionText($keyword);
        if ($normalizedKeyword === '') {
            return [];
        }

        if (array_key_exists($normalizedKeyword, $this->wilayahKeywordIdCache)) {
            return $this->wilayahKeywordIdCache[$normalizedKeyword];
        }

        $rows = DB::table('wilayah_kalsel')
            ->select(['id', 'nama', 'level'])
            ->get();

        if (in_array($normalizedKeyword, ['kalsel', 'kalimantan selatan'], true)) {
            $ids = $rows
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            return $this->wilayahKeywordIdCache[$normalizedKeyword] = $ids;
        }

        $ids = $rows
            ->filter(function ($row) use ($normalizedKeyword) {
                $name = (string) ($row->nama ?? '');
                $level = (string) ($row->level ?? '');
                $displayName = trim(($level === 'kota' ? 'Kota ' : 'Kabupaten ') . $name);

                return $this->regionPhraseMatches($name, $normalizedKeyword)
                    || $this->regionPhraseMatches($displayName, $normalizedKeyword);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        return $this->wilayahKeywordIdCache[$normalizedKeyword] = $ids;
    }

    private function likePattern(string $keyword): string
    {
        return '%' . strtolower($keyword) . '%';
    }

    private function statusFilterIncludes(array $filters, string $status): bool
    {
        $statuses = $filters['statuses'] ?? [];

        return empty($statuses) || in_array($status, $statuses, true);
    }

    private function mainMarkerMatchesFilters(array $item, array $filters): bool
    {
        $statusKey = ($item['status'] ?? '') === 'Belum Bekerja'
            ? 'belum_bekerja'
            : 'bekerja';

        if (!$this->statusFilterIncludes($filters, $statusKey)) {
            return false;
        }

        $linearitasFilter = $filters['linearitas'] ?? null;
        if ($linearitasFilter !== null) {
            $linearitas = trim((string) ($item['linearitas'] ?: 'Tidak Erat'));
            if ($linearitas !== $linearitasFilter) {
                return false;
            }
        }

        $bidangFilters = $filters['bidang'] ?? [];
        if (!empty($bidangFilters)) {
            $bidang = trim((string) ($item['bidang'] ?? ''));
            if (!in_array($bidang, $bidangFilters, true)) {
                return false;
            }
        }

        if (!$this->yearFilterMatches($item['tahun_lulus'] ?? null, $filters['tahun'] ?? null)) {
            return false;
        }

        $angkatan = $filters['angkatan'] ?? null;
        if ($angkatan !== null && (string) ($item['angkatan'] ?? '') !== (string) $angkatan) {
            return false;
        }

        return $this->keywordMatchesMainMarker($item, $filters);
    }

    private function studiLanjutMarkerMatchesFilters(array $item, array $filters): bool
    {
        if (!$this->statusFilterIncludes($filters, 'studi_lanjut')) {
            return false;
        }

        if (!$this->yearFilterMatches($item['tahun_lulus_alumni'] ?? null, $filters['tahun'] ?? null)) {
            return false;
        }

        $angkatan = $filters['angkatan'] ?? null;
        if ($angkatan !== null && (string) ($item['angkatan'] ?? '') !== (string) $angkatan) {
            return false;
        }

        return $this->keywordMatchesStudyMarker($item, $filters);
    }

    private function yearFilterMatches($yearValue, ?int $range): bool
    {
        if ($range === null) {
            return true;
        }

        $year = (int) $yearValue;
        if ($year <= 0) {
            return false;
        }

        $diff = ((int) date('Y')) - $year;

        return $diff >= 0 && $diff <= $range;
    }

    private function keywordMatchesMainMarker(array $item, array $filters): bool
    {
        $keyword = $filters['keyword'] ?? '';
        if ($keyword === '') {
            return true;
        }

        $scopes = $this->activeSearchScopes($filters);

        $matchesName = $scopes['nama']
            && $this->textContains([$item['nama'] ?? ''], $keyword);

        $matchesCompany = $scopes['perusahaan']
            && $this->textContains([$item['perusahaan'] ?? ''], $keyword);

        $matchesNim = $scopes['nim']
            && $this->textContains([$item['nim'] ?? ''], $keyword);

        return $matchesName || $matchesNim || $matchesCompany;
    }

    private function keywordMatchesStudyMarker(array $item, array $filters): bool
    {
        $keyword = $filters['keyword'] ?? '';
        if ($keyword === '') {
            return true;
        }

        $scopes = $this->activeSearchScopes($filters);

        $matchesName = $scopes['nama']
            && $this->textContains([$item['nama_lengkap'] ?? ''], $keyword);

        $matchesInstitution = $scopes['perusahaan']
            && $this->textContains([
                $item['kampus'] ?? '',
                $item['jenjang'] ?? '',
                $item['program_studi'] ?? '',
            ], $keyword);

        $matchesNim = $scopes['nim']
            && $this->textContains([$item['nim'] ?? ''], $keyword);

        return $matchesName || $matchesNim || $matchesInstitution;
    }

    private function activeSearchScopes(array $filters): array
    {
        $selected = $filters['search_scopes'] ?? [];
        $isAll = empty($selected);

        return [
            'nama' => $isAll || in_array('nama', $selected, true),
            'nim' => $isAll || in_array('nim', $selected, true),
            'perusahaan' => $isAll || in_array('perusahaan', $selected, true),
        ];
    }

    private function textContains(array $parts, string $keyword): bool
    {
        $haystack = strtolower(trim(implode(' ', array_filter($parts, function ($part) {
            return trim((string) $part) !== '';
        }))));

        return $haystack !== '' && str_contains($haystack, $keyword);
    }

    private function regionTextMatches(array $parts, string $keyword): bool
    {
        $keyword = $this->normalizeRegionText($keyword);
        if ($keyword === '') {
            return false;
        }

        foreach ($parts as $part) {
            if ($this->regionPhraseMatches((string) $part, $keyword)) {
                return true;
            }
        }

        return $this->regionPhraseMatches(implode(' ', $parts), $keyword);
    }

    private function regionPhraseMatches(string $text, string $phrase): bool
    {
        $text = $this->normalizeRegionText($text);
        $phrase = $this->normalizeRegionText($phrase);

        if ($text === '' || $phrase === '') {
            return false;
        }

        return preg_match('/(^|\s)' . preg_quote($phrase, '/') . '($|\s)/u', $text) === 1;
    }

    private function normalizeRegionText(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === 'kota baru') {
            return 'kotabaru';
        }

        if ($text === 'banjar baru') {
            return 'banjarbaru';
        }

        $text = preg_replace('/^(kabupaten|kab|kota)\s+/i', '', $text) ?? $text;

        return trim($text);
    }
}
