<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sangas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->string('nama', 100);
            $table->string('kode', 20)->unique();
            $table->string('deskripsi')->nullable();
            $table->foreignId('kepala_sanga_id')->nullable()->constrained('members')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            $table->index(['ambalan_id', 'kode']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->foreignId('sanga_id')->nullable()->constrained('sangas')->nullOnDelete()->after('ambalan_id');
            $table->index('sanga_id');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropForeign(['sanga_id']);
            $table->dropColumn('sanga_id');
        });

        Schema::dropIfExists('sangas');
    }
};
