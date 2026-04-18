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
        Schema::create('riwayat_pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumnis')->cascadeOnDelete();
            $table->foreignId('perusahaan_id')->nullable()->constrained('perusahaan')->nullOnDelete();

            $table->string('jabatan')->nullable();
            $table->string('bidang_pekerjaan')->nullable();
            $table->string('status_kerja')->nullable();
            $table->boolean('is_current')->default(false);

            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();

            $table->integer('masa_tunggu')->nullable();
            $table->string('status_karir')->nullable();
            $table->string('sumber_info')->nullable();
            $table->bigInteger('gaji_nominal')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('riwayat_pekerjaan');
    }
};
