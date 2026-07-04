<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE EXTENSION IF NOT EXISTS postgis');

        DB::statement('
            ALTER TABLE studi_lanjut
            ADD COLUMN IF NOT EXISTS geom geography(POINT, 4326)
        ');

        DB::statement('
            UPDATE studi_lanjut
            SET geom = ST_SetSRID(ST_MakePoint(longitude, latitude), 4326)::geography
            WHERE latitude IS NOT NULL
              AND longitude IS NOT NULL
              AND latitude BETWEEN -90 AND 90
              AND longitude BETWEEN -180 AND 180
              AND geom IS NULL
        ');

        DB::statement('
            CREATE INDEX IF NOT EXISTS idx_studi_lanjut_geom
            ON studi_lanjut USING GIST(geom)
        ');

        DB::unprepared('
            CREATE OR REPLACE FUNCTION sync_studi_lanjut_geom()
            RETURNS TRIGGER AS $$
            BEGIN
                IF NEW.latitude IS NOT NULL
                   AND NEW.longitude IS NOT NULL
                   AND NEW.latitude BETWEEN -90 AND 90
                   AND NEW.longitude BETWEEN -180 AND 180 THEN
                    NEW.geom := ST_SetSRID(
                        ST_MakePoint(NEW.longitude, NEW.latitude),
                        4326
                    )::geography;
                ELSE
                    NEW.geom := NULL;
                END IF;

                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS studi_lanjut_geom_sync ON studi_lanjut;

            CREATE TRIGGER studi_lanjut_geom_sync
            BEFORE INSERT OR UPDATE OF latitude, longitude ON studi_lanjut
            FOR EACH ROW
            EXECUTE FUNCTION sync_studi_lanjut_geom();
        ');
    }

    public function down(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS studi_lanjut_geom_sync ON studi_lanjut');
        DB::statement('DROP FUNCTION IF EXISTS sync_studi_lanjut_geom()');
        DB::statement('DROP INDEX IF EXISTS idx_studi_lanjut_geom');
        DB::statement('ALTER TABLE studi_lanjut DROP COLUMN IF EXISTS geom');
    }
};
