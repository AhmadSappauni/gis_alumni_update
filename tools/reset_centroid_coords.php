<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lat = (string) ($argv[1] ?? '-2.48338260');
$lng = (string) ($argv[2] ?? '117.89028530');

$lokasiQ = App\Models\LokasiPerusahaan::query()
    ->where('latitude', $lat)
    ->where('longitude', $lng);

$alamatQ = App\Models\AlamatAlumni::query()
    ->where('latitude', $lat)
    ->where('longitude', $lng);

$lokasiCount = (clone $lokasiQ)->count();
$alamatCount = (clone $alamatQ)->count();

if ($lokasiCount > 0) {
    (clone $lokasiQ)->update(['latitude' => null, 'longitude' => null]);
}
if ($alamatCount > 0) {
    (clone $alamatQ)->update(['latitude' => null, 'longitude' => null]);
}

fwrite(STDOUT, "reset_lokasi={$lokasiCount} reset_alamat={$alamatCount}\n");

