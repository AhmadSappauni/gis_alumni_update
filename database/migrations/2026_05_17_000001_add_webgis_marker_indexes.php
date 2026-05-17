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
        Schema::table('alumnis', function (Blueprint $table) {
            $table->index('nama_lengkap', 'idx_alumnis_nama');
        });

        Schema::table('alumni_akademik', function (Blueprint $table) {
            $table->index('angkatan', 'idx_akademik_angkatan');
            $table->index('tahun_lulus', 'idx_akademik_tahun_lulus');
            $table->index(['angkatan', 'tahun_lulus'], 'idx_akademik_ang_lulus');
        });

        Schema::table('alamat_alumni', function (Blueprint $table) {
            $table->index(['alumni_id', 'is_current'], 'idx_alamat_current');
            $table->index('kota', 'idx_alamat_kota');
            $table->index('provinsi', 'idx_alamat_prov');
            $table->index(['latitude', 'longitude'], 'idx_alamat_coords');
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->index('nama_perusahaan', 'idx_perusahaan_nama');
            $table->index('linearitas', 'idx_perusahaan_linearitas');
        });

        Schema::table('riwayat_pekerjaan', function (Blueprint $table) {
            $table->index(['is_current', 'status_kerja'], 'idx_kerja_current_status');
            $table->index(['alumni_id', 'is_current'], 'idx_kerja_alumni_current');
            $table->index('bidang_pekerjaan', 'idx_kerja_bidang');
            $table->index('status_karir', 'idx_kerja_status_karir');
        });

        Schema::table('lokasi_perusahaan', function (Blueprint $table) {
            $table->index('kota', 'idx_lokasi_kota');
            $table->index('provinsi', 'idx_lokasi_prov');
            $table->index(['latitude', 'longitude'], 'idx_lokasi_coords');
        });

        Schema::table('studi_lanjut', function (Blueprint $table) {
            $table->index('kampus', 'idx_studi_kampus');
            $table->index('kota_kampus', 'idx_studi_kota');
            $table->index('provinsi_kampus', 'idx_studi_prov');
            $table->index(['latitude', 'longitude'], 'idx_studi_coords');
            $table->index('tahun_masuk', 'idx_studi_tahun_masuk');
            $table->index('tahun_lulus', 'idx_studi_tahun_lulus');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('studi_lanjut', function (Blueprint $table) {
            $table->dropIndex('idx_studi_kampus');
            $table->dropIndex('idx_studi_kota');
            $table->dropIndex('idx_studi_prov');
            $table->dropIndex('idx_studi_coords');
            $table->dropIndex('idx_studi_tahun_masuk');
            $table->dropIndex('idx_studi_tahun_lulus');
        });

        Schema::table('lokasi_perusahaan', function (Blueprint $table) {
            $table->dropIndex('idx_lokasi_kota');
            $table->dropIndex('idx_lokasi_prov');
            $table->dropIndex('idx_lokasi_coords');
        });

        Schema::table('riwayat_pekerjaan', function (Blueprint $table) {
            $table->dropIndex('idx_kerja_current_status');
            $table->dropIndex('idx_kerja_alumni_current');
            $table->dropIndex('idx_kerja_bidang');
            $table->dropIndex('idx_kerja_status_karir');
        });

        Schema::table('perusahaan', function (Blueprint $table) {
            $table->dropIndex('idx_perusahaan_nama');
            $table->dropIndex('idx_perusahaan_linearitas');
        });

        Schema::table('alamat_alumni', function (Blueprint $table) {
            $table->dropIndex('idx_alamat_current');
            $table->dropIndex('idx_alamat_kota');
            $table->dropIndex('idx_alamat_prov');
            $table->dropIndex('idx_alamat_coords');
        });

        Schema::table('alumni_akademik', function (Blueprint $table) {
            $table->dropIndex('idx_akademik_angkatan');
            $table->dropIndex('idx_akademik_tahun_lulus');
            $table->dropIndex('idx_akademik_ang_lulus');
        });

        Schema::table('alumnis', function (Blueprint $table) {
            $table->dropIndex('idx_alumnis_nama');
        });
    }
};
