<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $columnsToDrop = [];

        if (Schema::hasColumn('lokasi_perusahaan', 'nama_cabang')) {
            $columnsToDrop[] = 'nama_cabang';
        }

        if (Schema::hasColumn('lokasi_perusahaan', 'is_head_office')) {
            $columnsToDrop[] = 'is_head_office';
        }

        if (empty($columnsToDrop)) {
            return;
        }

        Schema::table('lokasi_perusahaan', function (Blueprint $table) use ($columnsToDrop) {
            $table->dropColumn($columnsToDrop);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $needsNamaCabang = !Schema::hasColumn('lokasi_perusahaan', 'nama_cabang');
        $needsIsHeadOffice = !Schema::hasColumn('lokasi_perusahaan', 'is_head_office');

        if (!$needsNamaCabang && !$needsIsHeadOffice) {
            return;
        }

        Schema::table('lokasi_perusahaan', function (Blueprint $table) use ($needsNamaCabang, $needsIsHeadOffice) {
            if ($needsNamaCabang) {
                $table->string('nama_cabang')->nullable()->after('perusahaan_id');
            }

            if ($needsIsHeadOffice) {
                $table->boolean('is_head_office')->default(false)->after('longitude');
            }
        });
    }
};

