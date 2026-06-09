<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class PostgisDemoController extends Controller
{
    private const SAMPLE_LIMIT = 20;

    public function index(Request $request)
    {
        $this->guardDemoKey($request);

        return view('postgis-demo.index', [
            'dataUrl' => route('postgis-demo.data', [
                'key' => $request->query('key'),
            ]),
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $this->guardDemoKey($request);

        try {
            $plainRows = $this->queryTanpaPostgis();
        } catch (Throwable $exception) {
            return response()->json([
                'samples' => [],
                'meta' => [
                    'sample_limit' => self::SAMPLE_LIMIT,
                    'postgis_available' => false,
                    'message' => 'Data alumni belum dapat dibaca. Periksa koneksi database dan tabel alamat_alumni.',
                    'error' => config('app.debug') ? $exception->getMessage() : null,
                ],
            ]);
        }

        $postgisRows = collect();
        $postgisAvailable = true;
        $postgisMessage = 'Query PostGIS berhasil dijalankan pada tabel wilayah_kalsel.';
        $ids = $plainRows->pluck('alumni_id')->map(fn ($id) => (int) $id)->all();

        try {
            $postgisRows = $this->queryDenganPostgis($ids)->keyBy('alumni_id');
        } catch (Throwable $exception) {
            $postgisAvailable = false;
            $postgisMessage = 'Query PostGIS belum tersedia atau kolom geometry belum cocok. Demo tetap menampilkan data latitude/longitude biasa.';

            if (config('app.debug')) {
                $postgisMessage .= ' Detail: ' . $exception->getMessage();
            }
        }

        $samples = $plainRows
            ->map(function ($row) use ($postgisRows, $postgisAvailable) {
                $postgis = $postgisRows->get((int) $row->alumni_id);
                $wilayahTeks = $this->formatWilayahTeks($row);
                $wilayahPostgis = $postgisAvailable
                    ? $this->formatWilayahPostgis($postgis)
                    : null;

                return [
                    'nama_alumni' => (string) $row->nama_alumni,
                    'latitude' => (float) $row->latitude,
                    'longitude' => (float) $row->longitude,
                    'alamat_teks' => $this->formatAlamat($row),
                    'wilayah_tanpa_postgis' => $wilayahTeks,
                    'wilayah_postgis' => $wilayahPostgis,
                    'status_perbandingan' => $this->statusPerbandingan($wilayahTeks, $wilayahPostgis),
                ];
            })
            ->values();

        return response()->json([
            'samples' => $samples,
            'meta' => [
                'sample_limit' => self::SAMPLE_LIMIT,
                'sample_count' => $samples->count(),
                'postgis_available' => $postgisAvailable,
                'message' => $postgisMessage,
            ],
        ]);
    }

    private function guardDemoKey(Request $request): void
    {
        $configuredKey = (string) config('services.postgis_demo.key', '');
        $givenKey = (string) $request->query('key', '');

        if ($configuredKey === '' || !hash_equals($configuredKey, $givenKey)) {
            abort(404);
        }
    }

    private function queryTanpaPostgis(): Collection
    {
        /*
        | Query tanpa PostGIS:
        | Database hanya membaca latitude dan longitude sebagai angka biasa.
        | Wilayah diambil dari teks kota/provinsi yang tersimpan di tabel.
        */
        return DB::table('alumnis as a')
            ->join('alamat_alumni as aa', 'aa.alumni_id', '=', 'a.id')
            ->select([
                'a.id as alumni_id',
                'a.nama_lengkap as nama_alumni',
                'aa.alamat_lengkap',
                'aa.kota',
                'aa.provinsi',
                'aa.latitude',
                'aa.longitude',
            ])
            ->where('aa.is_current', true)
            ->whereNotNull('aa.latitude')
            ->whereNotNull('aa.longitude')
            ->orderBy('a.nama_lengkap')
            ->limit(self::SAMPLE_LIMIT)
            ->get();
    }

    private function queryDenganPostgis(array $alumniIds): Collection
    {
        if (empty($alumniIds)) {
            return collect();
        }

        $pointExpression = 'ST_SetSRID(ST_MakePoint(aa.longitude, aa.latitude), 4326)';

        /*
        | Query dengan PostGIS:
        | Titik alumni dibentuk langsung dari longitude/latitude memakai
        | ST_SetSRID(ST_MakePoint(longitude, latitude), 4326), lalu dicek
        | terhadap polygon wilayah_kalsel.geom menggunakan ST_Contains/ST_Within.
        */
        return DB::table('alamat_alumni as aa')
            ->leftJoin('wilayah_kalsel as wk', function ($join) use ($pointExpression) {
                $join->whereRaw("
                    ST_Contains(wk.geom, {$pointExpression})
                    OR ST_Within({$pointExpression}, wk.geom)
                ");
            })
            ->select([
                'aa.alumni_id',
                'wk.nama as wilayah_postgis',
                'wk.level as level_postgis',
            ])
            ->whereIn('aa.alumni_id', $alumniIds)
            ->where('aa.is_current', true)
            ->whereNotNull('aa.latitude')
            ->whereNotNull('aa.longitude')
            ->get();
    }

    private function formatAlamat(object $row): string
    {
        $parts = [
            $row->alamat_lengkap ?? null,
            $row->kota ?? null,
            $row->provinsi ?? null,
        ];

        $text = collect($parts)
            ->map(fn ($part) => trim((string) $part))
            ->filter()
            ->unique()
            ->implode(', ');

        return $text !== '' ? $text : 'Alamat teks tidak tersedia';
    }

    private function formatWilayahTeks(object $row): ?string
    {
        $kota = trim((string) ($row->kota ?? ''));
        $provinsi = trim((string) ($row->provinsi ?? ''));

        return $kota !== '' ? $kota : ($provinsi !== '' ? $provinsi : null);
    }

    private function formatWilayahPostgis(?object $row): ?string
    {
        if (!$row || empty($row->wilayah_postgis)) {
            return null;
        }

        $level = trim((string) ($row->level_postgis ?? ''));
        $prefix = $level === 'kota' ? 'Kota' : 'Kabupaten';

        return trim($prefix . ' ' . $row->wilayah_postgis);
    }

    private function statusPerbandingan(?string $wilayahTeks, ?string $wilayahPostgis): string
    {
        if (!$wilayahTeks || !$wilayahPostgis) {
            return 'tidak_terdeteksi';
        }

        return $this->normalisasiWilayah($wilayahTeks) === $this->normalisasiWilayah($wilayahPostgis)
            ? 'sama'
            : 'berbeda';
    }

    private function normalisasiWilayah(string $value): string
    {
        $text = strtolower(trim($value));
        $text = preg_replace('/[^a-z0-9]+/i', ' ', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;
        $text = trim($text);

        if ($text === 'kota baru') {
            return 'kotabaru';
        }

        if ($text === 'banjar baru') {
            return 'banjarbaru';
        }

        $text = preg_replace('/\b(kabupaten|kab|kota)\b/i', '', $text) ?? $text;
        $text = preg_replace('/\s+/', ' ', $text) ?? $text;

        return trim($text);
    }
}
