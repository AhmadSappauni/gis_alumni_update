<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // SIDANG-POSTGIS: Ekstensi PostGIS menyediakan tipe geography dan fungsi spasial yang dipakai WebGIS.
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        // SIDANG-POSTGIS: geom bertipe titik SRID 4326 menyimpan lokasi perusahaan untuk operasi spasial PostgreSQL.
        DB::statement('
            ALTER TABLE lokasi_perusahaan 
            ADD COLUMN IF NOT EXISTS geom geography(POINT, 4326)
        ');

        // SIDANG-POSTGIS: Koordinat lama disalin ke geom; longitude menjadi sumbu X dan latitude sumbu Y.
        // PENTING: ST_MakePoint pakai urutan (longitude, latitude) — bukan kebalikan!
        DB::statement('
            UPDATE lokasi_perusahaan 
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography 
            WHERE latitude IS NOT NULL 
              AND longitude IS NOT NULL 
              AND geom IS NULL
        ');

        // SIDANG-PERFORMA: Index GIST mempercepat pencarian dan pengujian relasi spasial pada geom perusahaan.
        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_lokasi_perusahaan_geom 
            ON lokasi_perusahaan USING GIST(geom)
        ');

        // SIDANG-POSTGIS: Alamat alumni juga memiliki geom untuk filter domisili berbasis polygon.
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
