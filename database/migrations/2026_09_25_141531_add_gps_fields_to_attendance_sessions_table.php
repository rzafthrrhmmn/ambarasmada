<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->decimal('latitude', 10, 8)->nullable()->after('lokasi');
            $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            $table->integer('radius')->nullable()->after('longitude'); // in meters
            $table->boolean('qr_dynamic')->default(false)->after('qr_token');
            $table->timestamp('qr_refreshed_at')->nullable()->after('qr_dynamic');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_sessions', function (Blueprint $table) {
            $table->dropColumn(['latitude', 'longitude', 'radius', 'qr_dynamic', 'qr_refreshed_at']);
        });
    }
};
