<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tkk_points', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 150);
            $table->string('slug', 150)->unique();
            $table->text('deskripsi')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active']);
        });

        Schema::create('tkk_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tkk_point_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bukti_kegiatan')->nullable();
            $table->string('status')->default('Pending');
            $table->text('catatan')->nullable();
            $table->timestamp('tgl_verifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['member_id', 'tkk_point_id']);
            $table->index(['status', 'tgl_verifikasi']);
        });

        Schema::create('member_tku', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tingkatan', 20);
            $table->timestamp('awarded_at')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'tingkatan']);
            $table->index(['tingkatan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_tku');
        Schema::dropIfExists('tkk_submissions');
        Schema::dropIfExists('tkk_points');
    }
};
