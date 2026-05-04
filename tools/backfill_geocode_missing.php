<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Http;

function geocodeFallback(?string $alamat, ?string $kota, ?string $provinsi): array
{
    $alamat = $alamat ? trim($alamat) : null;
    $kota = $kota ? trim($kota) : null;
    $provinsi = $provinsi ? trim($provinsi) : null;

    $queries = [];
    $queries[] = implode(', ', array_values(array_filter([$alamat, $kota, $provinsi, 'Indonesia'])));
    $queries[] = implode(', ', array_values(array_filter([$kota, $provinsi, 'Indonesia'])));
    $queries = array_values(array_unique(array_filter($queries)));

    foreach ($queries as $q) {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'WebGIS Alumni Pilkom (Laravel)',
            ])
                ->acceptJson()
                ->connectTimeout(8)
                ->timeout(20)
                ->retry(2, 500)
                ->get('https://nominatim.openstreetmap.org/search', [
                    'q' => $q,
                    'format' => 'json',
                    'limit' => 1,
                    'addressdetails' => 1,
                ]);

            if ($response->successful() && isset($response->json()[0])) {
                $geo = $response->json()[0];
                $displayName = trim(strtolower((string) ($geo['display_name'] ?? '')));
                $isJustIndonesia = $displayName === 'indonesia' || $displayName === 'republic of indonesia';
                if ($isJustIndonesia && strlen(trim($q)) > 15) {
                    usleep(350000);
                    continue;
                }
                usleep(350000);
                return [$geo['lat'] ?? null, $geo['lon'] ?? null];
            }

            usleep(350000);
        } catch (\Throwable $e) {
            // skip
        }
    }

    return [null, null];
}

$limit = (int) ($argv[1] ?? 50);

$alamatRows = App\Models\AlamatAlumni::query()
    ->where(function ($q) {
        $q->whereNull('latitude')->orWhereNull('longitude');
    })
    ->orderByDesc('id')
    ->limit($limit)
    ->get();

$lokasiRows = App\Models\LokasiPerusahaan::query()
    ->where(function ($q) {
        $q->whereNull('latitude')
            ->orWhereNull('longitude')
            // Banyak data "nyasar" karena ketemu centroid Indonesia; treat ini sebagai perlu perbaikan
            ->orWhere(function ($q2) {
                $q2->where('latitude', '-2.48338260')->where('longitude', '117.89028530');
            });
    })
    ->orderByDesc('id')
    ->limit($limit)
    ->get();

$fixedAlamat = 0;
foreach ($alamatRows as $row) {
    [$lat, $lng] = geocodeFallback($row->alamat_lengkap, $row->kota, $row->provinsi);
    if ($lat && $lng) {
        $row->update(['latitude' => $lat, 'longitude' => $lng]);
        $fixedAlamat++;
    }
}

$fixedLokasi = 0;
foreach ($lokasiRows as $row) {
    [$lat, $lng] = geocodeFallback($row->alamat_lengkap, $row->kota, $row->provinsi);
    if ($lat && $lng) {
        $row->update(['latitude' => $lat, 'longitude' => $lng]);
        $fixedLokasi++;
    }
}

fwrite(STDOUT, "fixed_alamat={$fixedAlamat} fixed_lokasi={$fixedLokasi}\n");
