<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // STEP 1: Pastikan extension PostGIS aktif di database ini
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // STEP 2: Tambah kolom geom ke lokasi_perusahaan
        DB::statement('
            ALTER TABLE lokasi_perusahaan 
            ADD COLUMN IF NOT EXISTS geom geography(POINT, 4326)
        ');

        // STEP 3: Populate kolom geom dari data lat/lng yang sudah ada
        // PENTING: ST_MakePoint pakai urutan (longitude, latitude) — bukan kebalikan!
        DB::statement('
            UPDATE lokasi_perusahaan 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography 
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND geom IS NULL
        ');

        // STEP 4: Buat spatial index GIST (ini yang bikin query PostGIS jadi cepat)
        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_lokasi_perusahaan_geom 
            ON lokasi_perusahaan USING GIST(geom)
        ');

        // Lakukan hal yang sama untuk alamat_alumni
        DB::statement('
            ALTER TABLE alamat_alumni 
            ADD COLUMN IF NOT EXISTS geom geography(POINT, 4326)
        ');
        DB::statement('
            UPDATE alamat_alumni 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography 
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND geom IS NULL
        ');
        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_alamat_alumni_geom 
            ON alamat_alumni USING GIST(geom)
        ');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_lokasi_perusahaan_geom');
        DB::statement('ALTER TABLE lokasi_perusahaan DROP COLUMN IF EXISTS geom');

        DB::statement('DROP INDEX IF EXISTS idx_alamat_alumni_geom');
        DB::statement('ALTER TABLE alamat_alumni DROP COLUMN IF EXISTS geom');

        // Note: extension postgis nggak di-drop karena mungkin dipakai aplikasi lain
    }
};