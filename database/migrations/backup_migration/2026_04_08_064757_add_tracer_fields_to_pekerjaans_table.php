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
        Schema::table('pekerjaans', function (Blueprint $table) {
            $table->integer('masa_tunggu')->after('jabatan')->nullable()->comment('dalam satuan bulan');
            $table->string('kabupaten_kota')->after('kota')->nullable()->comment('untuk mapping ke GeoJSON');
            $table->string('sumber_info')->nullable();
            $table->string('tingkat_instansi')->nullable(); // Lokal, Nasional, Internasional
            
            // Menambahkan kolom gaji dalam tipe data angka untuk keperluan statistik/chart
            $table->bigInteger('gaji_nominal')->after('gaji')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            //
        });
    }
};
