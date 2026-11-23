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
        Schema::table('ambalans', function (Blueprint $table) {
            $table->longText('sejarah')->nullable()->after('alamat');
            $table->longText('visi')->nullable()->after('sejarah');
            $table->longText('misi')->nullable()->after('visi');
            $table->longText('sandi_amalan')->nullable()->after('misi');
            $table->longText('aturan_tata_krama')->nullable()->after('sandi_amalan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ambalans', function (Blueprint $table) {
            $table->dropColumn(['sejarah', 'visi', 'misi', 'sandi_amalan', 'aturan_tata_krama']);
        });
    }
};
