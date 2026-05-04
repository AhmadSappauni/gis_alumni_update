<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$alumni = App\Models\Alumni::with([
    'alamat',
    'pekerjaan.perusahaan.lokasiAktif',
    'pekerjaan.perusahaan.lokasi',
])
    ->orderByDesc('id')
    ->first();

if (!$alumni) {
    fwrite(STDOUT, "No alumni rows found.\n");
    exit(0);
}

fwrite(STDOUT, "alumni_id={$alumni->id} nim={$alumni->nim}\n");
fwrite(
    STDOUT,
    'alamat_lat=' . ($alumni->alamat?->latitude ?? 'null') . ' lng=' . ($alumni->alamat?->longitude ?? 'null') . "\n"
);
fwrite(
    STDOUT,
    'alamat_kota=' . ($alumni->alamat?->kota ?? 'null') . ' alamat_prov=' . ($alumni->alamat?->provinsi ?? 'null') . "\n"
);

$job = $alumni->pekerjaan?->where('is_current', true)->first();
fwrite(STDOUT, 'has_job=' . ($job ? '1' : '0') . "\n");

if ($job) {
    $lokasi = $job->perusahaan?->lokasiAktif
        ?? $job->perusahaan?->lokasi?->sortByDesc('id')->first();

    fwrite(
        STDOUT,
        'job_status=' . ($job->status_kerja ?? 'null') .
        ' lokasi_lat=' . ($lokasi?->latitude ?? 'null') .
        ' lng=' . ($lokasi?->longitude ?? 'null') . "\n"
    );
    fwrite(
        STDOUT,
        'lokasi_kota=' . ($lokasi?->kota ?? 'null') . ' lokasi_prov=' . ($lokasi?->provinsi ?? 'null') . "\n"
    );
}
