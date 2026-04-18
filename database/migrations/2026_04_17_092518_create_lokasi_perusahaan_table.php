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
        Schema::create('lokasi_perusahaan', function (Blueprint $table) {
            $table->id();

            $table->foreignId('perusahaan_id')
                ->constrained('perusahaan')
                ->cascadeOnDelete();

            $table->string('nama_cabang')->nullable();
            $table->text('alamat_lengkap')->nullable();
            $table->string('kota')->nullable();
            $table->string('provinsi')->nullable();

            $table->decimal('latitude',10,8)->nullable();
            $table->decimal('longitude',11,8)->nullable();

            $table->boolean('is_head_office')->default(false);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lokasi_perusahaan');
    }
};
