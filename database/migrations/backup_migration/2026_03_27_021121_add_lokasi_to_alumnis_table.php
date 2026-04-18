<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('alumnis', function (Blueprint $table) {
            $table->string('kota_tinggal')->nullable()->after('no_hp');
            $table->text('alamat_tinggal')->nullable()->after('kota_tinggal');
            $table->string('latitude_tinggal')->nullable()->after('alamat_tinggal');
            $table->string('longitude_tinggal')->nullable()->after('latitude_tinggal');
        });
    }

    public function down(): void
    {
        Schema::table('alumnis', function (Blueprint $table) {
            $table->dropColumn(['kota_tinggal', 'alamat_tinggal', 'latitude_tinggal', 'longitude_tinggal']);
        });
    }
};