<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WilayahKalselSeeder extends Seeder
{
    public function run(): void
    {
        $path = public_path('data/data_kalsel.geojson');
        
        if (!file_exists($path)) {
            $this->command->error("File tidak ditemukan: {$path}");
            return;
        }

        $data = json_decode(file_get_contents($path), true);

        if (!isset($data['features']) || !is_array($data['features'])) {
            $this->command->error("Format GeoJSON tidak valid — tidak ada 'features'");
            return;
        }

        // Hapus data lama (kalau seeder dijalankan ulang)
        DB::table('wilayah_kalsel')->truncate();

        $count = 0;
        $skipped = 0;

        foreach ($data['features'] as $feature) {
            $props = $feature['properties'] ?? [];
            $namaAsli = $props['NAMOBJ'] ?? null;

            if (!$namaAsli) {
                $skipped++;
                continue;
            }

            // Inferensi level dari prefix "Kota "
            $isKota = stripos($namaAsli, 'Kota ') === 0;
            $level = $isKota ? 'kota' : 'kabupaten';
            
            // Bersihkan nama — buang prefix "Kota " supaya nama bersih
            $namaBersih = $isKota ? trim(substr($namaAsli, 5)) : $namaAsli;

            // OBJECTID sebagai reference ke source data
            $kodeRef = isset($props['OBJECTID']) ? (string) $props['OBJECTID'] : null;

            // Convert geometry GeoJSON ke PostGIS MultiPolygon
            $geomJson = json_encode($feature['geometry']);

            DB::insert("
                INSERT INTO wilayah_kalsel (nama, level, kode_bps, geom)
                VALUES (?, ?, ?, ST_Multi(ST_Force2D(ST_GeomFromGeoJSON(?))))
            ", [$namaBersih, $level, $kodeRef, $geomJson]);

            $count++;
            $this->command->info("  ✓ {$level}: {$namaBersih}");
        }

        $this->command->info("");
        $this->command->info("Total: {$count} wilayah ter-import" . ($skipped ? " ({$skipped} di-skip)" : ""));
    }
}