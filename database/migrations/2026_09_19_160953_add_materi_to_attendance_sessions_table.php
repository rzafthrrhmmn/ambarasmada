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
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->string('materi_path', 255)->nullable()->after('lokasi');
            $table->string('materi_nama', 255)->nullable()->after('materi_path');
            $table->string('materi_mime_type', 255)->nullable()->after('materi_nama');
            $table->unsignedBigInteger('materi_size')->nullable()->after('materi_mime_type');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'materi_path',
                'materi_nama',
                'materi_mime_type',
                'materi_size',
            ]);
        });
    }
};
