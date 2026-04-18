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
        Schema::table('alumnis', function (Blueprint $table) {
            $table->string('email')->nullable()->after('nama_lengkap');
            $table->string('no_hp')->nullable()->after('email');
        });
    }

    public function down()
    {
        Schema::table('alumnis', function (Blueprint $table) {
            $table->dropColumn(['email', 'no_hp']);
        });
    }
};
