<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('issued_by')->constrained('users')->cascadeOnDelete();
            $table->string('nomor_sertifikat')->unique();
            $table->string('jenis');
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_diterbitkan');
            $table->string('file_path')->nullable();
            $table->enum('status', ['Draft', 'Diterbitkan', 'Dibatalkan'])->default('Draft');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
