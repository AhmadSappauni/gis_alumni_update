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

            if (!Schema::hasColumn('pekerjaans', 'is_current')) {
                $table->boolean('is_current')->default(true)->after('nim');
            }

            if (!Schema::hasColumn('pekerjaans', 'status_kerja')) {
                $table->string('status_kerja')->nullable()->after('is_current');
            }

        });
    }

    public function down()
    {
        Schema::table('pekerjaans', function (Blueprint $table) {
            $table->dropColumn(['is_current', 'status_kerja']);
        });
    }
};
