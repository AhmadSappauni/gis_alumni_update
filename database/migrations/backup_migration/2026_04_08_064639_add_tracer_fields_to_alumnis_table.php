<?php
use Illuminate\Support\Facades\DB; // Pastikan ini ditambahkan di bagian atas file
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambahkan kolom baru pakai cara biasa
        Schema::table('alumnis', function (Blueprint $table) {
            $table->integer('tahun_yudisium')->after('tahun_lulus')->nullable();
            $table->integer('nilai_toefl')->after('no_hp')->nullable();
        });

        // 2. Ubah tipe data Varchar ke Decimal pakai Raw SQL khusus PostgreSQL
        // (Kita pakai NULLIF untuk mencegah error kalau ada data yang isinya string kosong "")
        DB::statement("ALTER TABLE alumnis ALTER COLUMN latitude_tinggal TYPE numeric(10,8) USING NULLIF(latitude_tinggal, '')::numeric");
        DB::statement("ALTER TABLE alumnis ALTER COLUMN longitude_tinggal TYPE numeric(11,8) USING NULLIF(longitude_tinggal, '')::numeric");
    }

    public function down(): void
    {
        Schema::table('alumnis', function (Blueprint $table) {
            $table->dropColumn(['tahun_yudisium', 'nilai_toefl']);
            // Kembalikan ke varchar jika di-rollback
            $table->string('latitude_tinggal')->change();
            $table->string('longitude_tinggal')->change();
        });
    }
};
