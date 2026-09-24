<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assessor_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('periode');
            $table->decimal('nilai_kehadiran', 5, 2)->default(0);
            $table->decimal('nilai_disiplin', 5, 2)->default(0);
            $table->decimal('nilai_keterampilan', 5, 2)->default(0);
            $table->decimal('nilai_kepemimpinan', 5, 2)->default(0);
            $table->decimal('nilai_keseluruhan', 5, 2)->default(0);
            $table->text('catatan')->nullable();
            $table->enum('status', ['Draft', 'Selesai'])->default('Draft');
            $table->timestamps();
        });

        Schema::create('assessment_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assessment_id')->constrained()->cascadeOnDelete();
            $table->string('kategori');
            $table->string('deskripsi');
            $table->decimal('nilai', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assessment_details');
        Schema::dropIfExists('assessments');
    }
};
