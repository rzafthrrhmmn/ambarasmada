<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->json('placeholder_values')->nullable()->after('template_id');
        });

        Schema::table('letter_templates', function (Blueprint $table) {
            $table->json('placeholders')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('letters', function (Blueprint $table) {
            $table->dropColumn('placeholder_values');
        });

        Schema::table('letter_templates', function (Blueprint $table) {
            $table->dropColumn('placeholders');
        });
    }
};
