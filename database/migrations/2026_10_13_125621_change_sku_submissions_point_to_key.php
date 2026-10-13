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
        Schema::table('sku_submissions', function (Blueprint $table) {
            $table->dropForeign(['sku_point_id']);
            $table->dropUnique(['member_id', 'sku_point_id']);
            $table->dropColumn('sku_point_id');
            $table->string('sku_point_key', 50)->after('member_id');
            $table->unique(['member_id', 'sku_point_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sku_submissions', function (Blueprint $table) {
            $table->dropUnique(['member_id', 'sku_point_key']);
            $table->dropColumn('sku_point_key');
            $table->foreignId('sku_point_id')->constrained()->cascadeOnDelete()->after('member_id');
            $table->unique(['member_id', 'sku_point_id']);
        });
    }
};
