<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('username', 30)->change();
            $table->string('role', 30)->default('Anggota')->change();
        });

        Schema::table('members', function (Blueprint $table): void {
            $table->string('angkatan', 3)->nullable()->index();
            $table->unsignedInteger('nomor_urut')->nullable();
            $table->string('nta_username', 30)->nullable()->unique();
            $table->string('status_aktif', 30)->default('Aktif')->change();
        });

        $prefix = (string) config('app.gudep_prefix', '31082008');
        if (! preg_match('/^\d{1,22}$/', $prefix)) {
            throw new RuntimeException('GUDEP_PREFIX must contain 1 to 22 digits.');
        }

        $counters = [];
        $members = DB::table('members')->orderBy('id')->get();
        foreach ($members as $member) {
            $matches = [];
            if (preg_match('/^\d{1,22}\.(\d{3})\.(\d{3})$/', (string) $member->nta, $matches) === 1) {
                $angkatan = $matches[1];
                $nomorUrut = (int) $matches[2];
            } else {
                $angkatan = '001';
                $nomorUrut = ($counters[$angkatan] ?? 0) + 1;
            }

            $counters[$angkatan] = max($nomorUrut, $counters[$angkatan] ?? 0);
            $ntaUsername = $member->nta !== null && strlen((string) $member->nta) <= 30
                ? (string) $member->nta
                : sprintf('%s.%s.%03d', $prefix, $angkatan, $nomorUrut);

            DB::table('members')->where('id', $member->id)->update([
                'angkatan' => $angkatan,
                'nomor_urut' => $nomorUrut,
                'nta_username' => $ntaUsername,
            ]);
        }

        Schema::table('members', function (Blueprint $table): void {
            $table->string('angkatan', 3)->nullable(false)->change();
            $table->unsignedInteger('nomor_urut')->nullable(false)->change();
            $table->string('nta_username', 30)->nullable(false)->change();
            $table->unique(['angkatan', 'nomor_urut']);
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table): void {
            $table->dropUnique(['angkatan', 'nomor_urut']);
            $table->dropColumn(['angkatan', 'nomor_urut', 'nta_username']);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->string('username')->change();
            $table->string('role')->default('Anggota')->change();
        });
    }
};
