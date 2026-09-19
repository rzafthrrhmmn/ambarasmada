<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sku_submission_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sku_submission_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('tipe')->default('photo');
            $table->text('caption')->nullable();
            $table->timestamps();

            $table->index(['sku_submission_id', 'tipe', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_submission_media');
    }
};
