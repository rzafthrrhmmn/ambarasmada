<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->string('kode');
            $table->text('deskripsi')->nullable();
            $table->timestamps();
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('peran');
            $table->timestamps();

            $table->unique(['team_id', 'member_id']);
        });

        Schema::create('team_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->constrained('members')->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->date('tenggat')->nullable();
            $table->enum('status', ['Belum Dimulai', 'Berlangsung', 'Selesai', 'Terlewat'])->default('Belum Dimulai');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('team_tasks');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
    }
};
