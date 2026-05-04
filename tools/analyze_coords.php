<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

function isOutOfIndonesia(?float $lat, ?float $lng): bool
{
    if ($lat === null || $lng === null) {
        return false;
    }

    // Bounding box Indonesia (rough): lat [-11.5, 6.5], lng [94.0, 141.5]
    return $lat < -11.5 || $lat > 6.5 || $lng < 94.0 || $lng > 141.5;
}

function isSuspicious(?float $lat, ?float $lng): bool
{
    if ($lat === null || $lng === null) {
        return false;
    }
    if (abs($lat) < 0.0001 && abs($lng) < 0.0001) {
        return true;
    }
    return isOutOfIndonesia($lat, $lng);
}

$limit = (int) ($argv[1] ?? 15);

$alamat = App\Models\AlamatAlumni::query()
    ->selectRaw("latitude::text as lat_text, longitude::text as lng_text, count(*) as c")
    ->whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->groupBy('lat_text', 'lng_text')
    ->orderByDesc('c')
    ->limit($limit)
    ->get();

$lokasi = App\Models\LokasiPerusahaan::query()
    ->selectRaw("latitude::text as lat_text, longitude::text as lng_text, count(*) as c")
    ->whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->groupBy('lat_text', 'lng_text')
    ->orderByDesc('c')
    ->limit($limit)
    ->get();

fwrite(STDOUT, "Top AlamatAlumni coords:\n");
foreach ($alamat as $row) {
    $lat = is_numeric($row->lat_text) ? (float) $row->lat_text : null;
    $lng = is_numeric($row->lng_text) ? (float) $row->lng_text : null;
    $flag = isSuspicious($lat, $lng) ? " !!" : "";
    fwrite(STDOUT, "  {$row->c}x {$row->lat_text},{$row->lng_text}{$flag}\n");
}

fwrite(STDOUT, "\nTop LokasiPerusahaan coords:\n");
foreach ($lokasi as $row) {
    $lat = is_numeric($row->lat_text) ? (float) $row->lat_text : null;
    $lng = is_numeric($row->lng_text) ? (float) $row->lng_text : null;
    $flag = isSuspicious($lat, $lng) ? " !!" : "";
    fwrite(STDOUT, "  {$row->c}x {$row->lat_text},{$row->lng_text}{$flag}\n");
}

$badAlamat = App\Models\AlamatAlumni::query()
    ->whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->get()
    ->filter(function ($row) {
        $lat = is_numeric($row->latitude) ? (float) $row->latitude : null;
        $lng = is_numeric($row->longitude) ? (float) $row->longitude : null;
        return isSuspicious($lat, $lng);
    })
    ->take(10);

$badLokasi = App\Models\LokasiPerusahaan::query()
    ->whereNotNull('latitude')
    ->whereNotNull('longitude')
    ->get()
    ->filter(function ($row) {
        $lat = is_numeric($row->latitude) ? (float) $row->latitude : null;
        $lng = is_numeric($row->longitude) ? (float) $row->longitude : null;
        return isSuspicious($lat, $lng);
    })
    ->take(10);

fwrite(STDOUT, "\nSample suspicious AlamatAlumni:\n");
foreach ($badAlamat as $row) {
    fwrite(
        STDOUT,
        "  id={$row->id} alumni_id={$row->alumni_id} lat={$row->latitude} lng={$row->longitude} kota=" .
        ($row->kota ?? 'null') . " prov=" . ($row->provinsi ?? 'null') . "\n"
    );
}

fwrite(STDOUT, "\nSample suspicious LokasiPerusahaan:\n");
foreach ($badLokasi as $row) {
    fwrite(
        STDOUT,
        "  id={$row->id} perusahaan_id={$row->perusahaan_id} lat={$row->latitude} lng={$row->longitude} kota=" .
        ($row->kota ?? 'null') . " prov=" . ($row->provinsi ?? 'null') . "\n"
    );
}

