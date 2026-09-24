<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->string('waktu_kegiatan', 255)->nullable()->after('tgl_surat');
            $table->string('lokasi_kegiatan', 255)->nullable()->after('waktu_kegiatan');
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn(['waktu_kegiatan', 'lokasi_kegiatan']);
        });
    }
};
