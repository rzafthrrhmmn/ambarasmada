<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('konten')->nullable();
            $table->string('kategori');
            $table->string('video_url')->nullable();
            $table->string('file_path')->nullable();
            $table->date('tanggal')->nullable();
            $table->enum('status', ['Draft', 'Aktif', 'Selesai'])->default('Draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('training_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('training_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->boolean('completed')->default(false);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['training_id', 'member_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_progress');
        Schema::dropIfExists('trainings');
    }
};
