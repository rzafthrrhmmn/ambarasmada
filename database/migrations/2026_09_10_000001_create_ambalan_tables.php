<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ambalans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('kode')->unique();
            $table->text('alamat')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });

        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nta')->unique()->nullable();
            $table->string('nama_lengkap');
            $table->string('kelas');
            $table->string('tingkatan')->default('Tamu');
            $table->smallInteger('tahun_lulus')->nullable();
            $table->string('status_aktif')->default('Aktif');
            $table->string('no_hp')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ambalan_id', 'status_aktif']);
            $table->index(['tingkatan', 'status_aktif']);
        });

        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('status_saat_ini')->default('Lainnya');
            $table->string('instansi_kampus')->nullable();
            $table->string('pekerjaan')->nullable();
            $table->string('domisili')->nullable();
            $table->string('media_sosial')->nullable();
            $table->boolean('show_contact')->default(false);
            $table->timestamps();
        });

        Schema::create('sku_points', function (Blueprint $table) {
            $table->id();
            $table->string('tingkatan');
            $table->smallInteger('nomor_poin')->unsigned();
            $table->text('deskripsi_poin');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['tingkatan', 'nomor_poin']);
            $table->index(['tingkatan', 'is_active']);
        });

        Schema::create('sku_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sku_point_id')->constrained()->cascadeOnDelete();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('bukti_kegiatan')->nullable();
            $table->string('status')->default('Pending');
            $table->text('catatan')->nullable();
            $table->timestamp('tgl_verifikasi')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['member_id', 'sku_point_id']);
            $table->index(['status', 'tgl_verifikasi']);
        });

        Schema::create('attendance_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->date('tanggal');
            $table->string('lokasi')->nullable();
            $table->string('qr_token')->unique();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ambalan_id', 'tanggal']);
        });

        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->string('keterangan')->default('Hadir');
            $table->text('catatan')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['attendance_session_id', 'member_id']);
            $table->index(['attendance_session_id', 'keterangan']);
        });

        Schema::create('finance_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nama');
            $table->string('jenis')->default('Masuk');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('finance_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->date('starts_at');
            $table->date('ends_at');
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->index(['ambalan_id', 'is_closed']);
        });

        Schema::create('finances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('finance_categories')->nullOnDelete();
            $table->foreignId('period_id')->nullable()->constrained('finance_periods')->nullOnDelete();
            $table->string('jenis_transaksi');
            $table->decimal('nominal', 12, 2);
            $table->text('keterangan');
            $table->string('status')->default('Draft');
            $table->string('receipt_no')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->date('tgl_transaksi');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ambalan_id', 'tgl_transaksi']);
            $table->index(['jenis_transaksi', 'status']);
        });

        Schema::create('donations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->decimal('nominal', 12, 2);
            $table->string('bukti_transfer');
            $table->string('keterangan_alokasi')->nullable();
            $table->string('status_verifikasi')->default('Pending');
            $table->text('catatan')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['status_verifikasi', 'verified_at']);
        });

        Schema::create('inventories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->constrained()->cascadeOnDelete();
            $table->string('kode_barang')->unique();
            $table->string('nama_barang');
            $table->string('jenis')->default('Aset');
            $table->string('satuan')->default('Unit');
            $table->integer('jumlah')->default(0);
            $table->string('kondisi')->default('Baik');
            $table->string('status_pinjam')->default('Tersedia');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['ambalan_id', 'status_pinjam']);
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->string('jenis');
            $table->integer('jumlah');
            $table->string('referensi')->nullable();
            $table->foreignId('actor_id')->constrained('users')->restrictOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['inventory_id', 'created_at']);
        });

        Schema::create('inventory_loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained()->cascadeOnDelete();
            $table->foreignId('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('peminjam_nama');
            $table->date('tgl_pinjam');
            $table->date('tgl_kembali')->nullable();
            $table->string('status')->default('Dipinjam');
            $table->string('kondisi')->default('Baik');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index(['inventory_id', 'status']);
        });

        Schema::create('member_logbooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('kegiatan');
            $table->text('refleksi');
            $table->string('bukti')->nullable();
            $table->timestamps();

            $table->index(['member_id', 'tanggal']);
        });

        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ambalan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('judul');
            $table->text('isi');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['published_at', 'created_at']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->bigInteger('entity_id')->unsigned();
            $table->jsonb('metadata')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id', 'created_at']);
        });

        Schema::create('pwa_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('endpoint')->unique();
            $table->string('p256dh')->nullable();
            $table->string('auth')->nullable();
            $table->timestamp('last_seen')->nullable();
            $table->timestamps();
        });

        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('title');
            $table->text('body');
            $table->jsonb('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('pwa_devices');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('announcements');
        Schema::dropIfExists('member_logbooks');
        Schema::dropIfExists('inventory_loans');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventories');
        Schema::dropIfExists('donations');
        Schema::dropIfExists('finances');
        Schema::dropIfExists('finance_periods');
        Schema::dropIfExists('finance_categories');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('attendance_sessions');
        Schema::dropIfExists('sku_submissions');
        Schema::dropIfExists('sku_points');
        Schema::dropIfExists('alumni_profiles');
        Schema::dropIfExists('members');
        Schema::dropIfExists('ambalans');
    }
};
