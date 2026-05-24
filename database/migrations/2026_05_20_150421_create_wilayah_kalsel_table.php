<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("
            CREATE TABLE wilayah_kalsel (
                id SERIAL PRIMARY KEY,
                nama VARCHAR(100) NOT NULL,
                level VARCHAR(20) NOT NULL,
                kode_bps VARCHAR(10),
                geom geometry(MultiPolygon, 4326) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        DB::statement('CREATE INDEX idx_wilayah_kalsel_geom ON wilayah_kalsel USING GIST(geom)');
        DB::statement('CREATE INDEX idx_wilayah_kalsel_nama ON wilayah_kalsel(nama)');
        DB::statement('CREATE INDEX idx_wilayah_kalsel_level ON wilayah_kalsel(level)');
    }

    public function down(): void
    {
        DB::statement('DROP TABLE IF EXISTS wilayah_kalsel');
    }
};