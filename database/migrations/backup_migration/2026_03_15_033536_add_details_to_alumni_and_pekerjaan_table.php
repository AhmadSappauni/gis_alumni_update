<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        // Update tabel alumnis
        Schema::table('alumnis', function (Blueprint $table) {
            $table->integer('angkatan')->nullable()->after('nama_lengkap');
            $table->text('judul_skripsi')->nullable()->after('tahun_lulus');
            $table->string('foto_profil')->nullable()->after('judul_skripsi');
        });

        // Update tabel pekerjaans
        Schema::table('pekerjaans', function (Blueprint $table) {
            $table->string('bidang_pekerjaan')->nullable()->after('jabatan');
            $table->string('gaji')->nullable()->after('nama_perusahaan');
            $table->string('kota')->nullable()->after('gaji');
            $table->string('link_linkedin')->nullable()->after('kota');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni_and_pekerjaan', function (Blueprint $table) {
            //
        });
    }
};
