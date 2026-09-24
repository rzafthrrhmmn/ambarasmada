<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('judul');
            $table->text('agenda')->nullable();
            $table->date('tanggal');
            $table->time('waktu_mulai')->nullable();
            $table->time('waktu_selesai')->nullable();
            $table->string('lokasi')->nullable();
            $table->enum('jenis', ['Musyawarah', 'Rapat Pembina', 'Rapat Anggota', 'Sidang', 'Lainnya'])->default('Musyawarah');
            $table->enum('status', ['Draft', 'Berlangsung', 'Selesai', 'Dibatalkan'])->default('Draft');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_attendees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['Hadir', 'Izin', 'Tidak Hadir'])->default('Hadir');
            $table->timestamps();

            $table->unique(['meeting_id', 'member_id']);
        });

        Schema::create('meeting_minutes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->text('notulen');
            $table->text('keputusan')->nullable();
            $table->text('tindak_lanjut')->nullable();
            $table->timestamps();
        });

        Schema::create('meeting_agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->string('judul');
            $table->text('deskripsi')->nullable();
            $table->integer('urutan')->default(0);
            $table->timestamps();
        });

        Schema::create('meeting_votes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agenda_id')->nullable()->constrained('meeting_agendas')->cascadeOnDelete();
            $table->enum('pilihan', ['Setuju', 'Tidak Setuju', 'Abstain'])->default('Setuju');
            $table->timestamps();

            $table->unique(['meeting_id', 'member_id', 'agenda_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_votes');
        Schema::dropIfExists('meeting_minutes');
        Schema::dropIfExists('meeting_attendees');
        Schema::dropIfExists('meeting_agendas');
        Schema::dropIfExists('meetings');
    }
};
