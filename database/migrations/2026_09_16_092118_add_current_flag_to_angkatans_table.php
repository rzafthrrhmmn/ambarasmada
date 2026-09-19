<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('angkatans', function (Blueprint $table): void {
            $table->boolean('is_current')->default(false)->after('is_active');
            $table->index('is_current');
        });
    }

    public function down(): void
    {
        Schema::table('angkatans', function (Blueprint $table): void {
            $table->dropIndex(['is_current']);
            $table->dropColumn('is_current');
        });
    }
};
