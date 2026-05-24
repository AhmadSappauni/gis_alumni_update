<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared("
            CREATE OR REPLACE FUNCTION update_geom_from_latlng() 
            RETURNS TRIGGER AS \$\$
            BEGIN
                IF NEW.latitude IS NOT NULL AND NEW.longitude IS NOT NULL THEN
                    NEW.geom := ST_SetSRID(ST_MakePoint(NEW.longitude, NEW.latitude), 4326)::geography;
                ELSE
                    NEW.geom := NULL;
                END IF;
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;

            DROP TRIGGER IF EXISTS lokasi_perusahaan_geom_sync ON lokasi_perusahaan;
            CREATE TRIGGER lokasi_perusahaan_geom_sync 
            BEFORE INSERT OR UPDATE OF latitude, longitude ON lokasi_perusahaan
            FOR EACH ROW EXECUTE FUNCTION update_geom_from_latlng();

            DROP TRIGGER IF EXISTS alamat_alumni_geom_sync ON alamat_alumni;
            CREATE TRIGGER alamat_alumni_geom_sync 
            BEFORE INSERT OR UPDATE OF latitude, longitude ON alamat_alumni
            FOR EACH ROW EXECUTE FUNCTION update_geom_from_latlng();
        ");
    }

    public function down(): void
    {
        DB::unprepared("
            DROP TRIGGER IF EXISTS lokasi_perusahaan_geom_sync ON lokasi_perusahaan;
            DROP TRIGGER IF EXISTS alamat_alumni_geom_sync ON alamat_alumni;
            DROP FUNCTION IF EXISTS update_geom_from_latlng();
        ");
    }
};