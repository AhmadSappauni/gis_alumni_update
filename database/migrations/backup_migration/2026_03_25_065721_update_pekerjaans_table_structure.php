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
        Schema::table('pekerjaans', function (Blueprint $table) {
            // Hapus unique constraint pada NIM jika sebelumnya ada
            // $table->dropUnique(['nim']); 

            $table->string('status_kerja')->default('Bekerja'); 
            // Isinya: 'Bekerja', 'Wiraswasta', 'Mencari Kerja', 'Studi Lanjut'
            
            $table->boolean('is_current')->default(true); 
            // true = Pekerjaan sekarang, false = Riwayat lama
            
            $table->date('tanggal_mulai')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
