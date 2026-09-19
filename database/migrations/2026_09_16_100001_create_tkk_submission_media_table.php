<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tkk_submission_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tkk_submission_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('tipe')->default('photo');
            $table->string('caption', 255)->nullable();
            $table->timestamps();

            $table->index(['tkk_submission_id', 'tipe']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tkk_submission_media');
    }
};
