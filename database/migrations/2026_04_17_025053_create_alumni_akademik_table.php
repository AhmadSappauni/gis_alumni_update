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
        Schema::create('alumni_akademik', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alumni_id')->constrained('alumnis')->cascadeOnDelete();
            $table->integer('angkatan')->nullable();
            $table->integer('tahun_lulus')->nullable();
            $table->integer('tahun_yudisium')->nullable();
            $table->text('judul_skripsi')->nullable();
            $table->decimal('ipk',3,2)->nullable();
            $table->integer('nilai_toefl')->nullable();
            $table->integer('lama_studi')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_akademik');
    }
};
