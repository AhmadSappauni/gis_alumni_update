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
        if (!Schema::hasColumn('riwayat_pekerjaan', 'sumber_info')) {
            return;
        }

        Schema::table('riwayat_pekerjaan', function (Blueprint $table) {
            $table->dropColumn('sumber_info');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('riwayat_pekerjaan', 'sumber_info')) {
            return;
        }

        Schema::table('riwayat_pekerjaan', function (Blueprint $table) {
            $table->string('sumber_info')->nullable()->after('status_karir');
        });
    }
};

