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
        Schema::table('studi_lanjut', function (Blueprint $table) {
            if (!Schema::hasColumn('studi_lanjut', 'alamat_kampus')) {
                $table->text('alamat_kampus')->nullable()->after('kampus');
            }

            if (!Schema::hasColumn('studi_lanjut', 'kota_kampus')) {
                $table->string('kota_kampus')->nullable()->after('alamat_kampus');
            }

            if (!Schema::hasColumn('studi_lanjut', 'provinsi_kampus')) {
                $table->string('provinsi_kampus')->nullable()->after('kota_kampus');
            }

            if (!Schema::hasColumn('studi_lanjut', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('provinsi_kampus');
            }

            if (!Schema::hasColumn('studi_lanjut', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $toDrop = [];

        foreach (['alamat_kampus', 'kota_kampus', 'provinsi_kampus', 'latitude', 'longitude'] as $col) {
            if (Schema::hasColumn('studi_lanjut', $col)) {
                $toDrop[] = $col;
            }
        }

        if (empty($toDrop)) {
            return;
        }

        Schema::table('studi_lanjut', function (Blueprint $table) use ($toDrop) {
            $table->dropColumn($toDrop);
        });
    }
};

