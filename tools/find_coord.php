<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lat = $argv[1] ?? null;
$lng = $argv[2] ?? null;
if ($lat === null || $lng === null) {
    fwrite(STDOUT, "Usage: php tools/find_coord.php <lat> <lng>\n");
    exit(1);
}

fwrite(STDOUT, "LokasiPerusahaan matching {$lat},{$lng}:\n");
$rows = App\Models\LokasiPerusahaan::query()
    ->where('latitude', (string) $lat)
    ->where('longitude', (string) $lng)
    ->orderByDesc('id')
    ->limit(30)
    ->get();

foreach ($rows as $row) {
    $perusahaan = $row->perusahaan?->nama_perusahaan;
    fwrite(
        STDOUT,
        "  id={$row->id} perusahaan_id={$row->perusahaan_id} nama=" . ($perusahaan ?? 'null') .
        " kota=" . ($row->kota ?? 'null') . " prov=" . ($row->provinsi ?? 'null') .
        " alamat=" . ($row->alamat_lengkap ?? 'null') . "\n"
    );
}

