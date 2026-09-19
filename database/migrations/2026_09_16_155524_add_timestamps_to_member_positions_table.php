<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('member_positions', 'created_at')) {
            return;
        }

        Schema::table('member_positions', function (Blueprint $table) {
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::table('member_positions', function (Blueprint $table) {
            $table->dropTimestamps();
        });
    }
};
