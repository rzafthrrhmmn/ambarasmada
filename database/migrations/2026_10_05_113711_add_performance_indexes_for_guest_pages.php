<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('announcements')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->index(['published_at', 'is_published'], 'announcements_published_idx');
                $table->index('ambalan_id', 'announcements_ambalan_idx');
            });
        }

        if (Schema::hasTable('gallery')) {
            Schema::table('gallery', function (Blueprint $table) {
                $table->index(['created_at', 'ambalan_id'], 'gallery_created_idx');
                $table->index('kategori', 'gallery_kategori_idx');
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->index(['status_aktif', 'ambalan_id'], 'members_status_idx');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('announcements')) {
            Schema::table('announcements', function (Blueprint $table) {
                $table->dropIndex('announcements_published_idx');
                $table->dropIndex('announcements_ambalan_idx');
            });
        }

        if (Schema::hasTable('gallery')) {
            Schema::table('gallery', function (Blueprint $table) {
                $table->dropIndex('gallery_created_idx');
                $table->dropIndex('gallery_kategori_idx');
            });
        }

        if (Schema::hasTable('members')) {
            Schema::table('members', function (Blueprint $table) {
                $table->dropIndex('members_status_idx');
            });
        }
    }
};
