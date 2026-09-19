<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('letters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nomor_surat', 100)->nullable();
            $table->enum('jenis_surat', ['Masuk', 'Keluar', 'Keputusan'])->default('Masuk');
            $table->string('perihal', 255);
            $table->string('tujuan_pengirim', 150)->nullable();
            $table->date('tgl_surat')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['nomor_surat', 'jenis_surat']);
            $table->index(['created_by_user_id', 'created_at']);
        });

        Schema::create('learning_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama', 200);
            $table->string('deskripsi', 255)->nullable();
            $table->text('konten')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_restricted')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_restricted', 'created_at']);
        });

        Schema::create('activity_guides', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kecamatan', 100);
            $table->string('nama', 200);
            $table->longText('teks_susunan_upacara')->nullable();
            $table->json('checklist_perlengkapan')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['kecamatan', 'created_at']);
        });

        Schema::create('ambalan_medias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama', 200);
            $table->longText('lirik')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ambalan_medias');
        Schema::dropIfExists('activity_guides');
        Schema::dropIfExists('learning_materials');
        Schema::dropIfExists('letters');
    }
};
