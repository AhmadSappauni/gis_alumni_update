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
            // Kita gunakan string: 'Utama', 'Sampingan', 'Riwayat'
            // Default 'Utama' supaya data lama tidak kosong
            $table->string('status_karir')->default('Utama');
        });
    }

    public function down()
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            $table->dropColumn('status_karir');
        });
    }
};
