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
            // Tambahkan kolom is_current dengan default true agar data lama langsung muncul
            $table->boolean('is_current')->default(true)->after('nim'); 
            $table->string('status_kerja')->nullable()->after('is_current'); // Optional: buat status 'Bekerja'/'Nganggur'
        });
    }

    public function down()
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            $table->dropColumn(['is_current', 'status_kerja']);
        });
    }
};
