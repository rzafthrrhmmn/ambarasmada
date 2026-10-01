<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Username_users memakai email yang didaftarkan pengguna, bukan lagi NTA
 * numerik (mis. 31082008.30.001).
 *
 * Nilai NTA tetap diteruskan ke tabel `members` (kolom `nta` dan
 * `nta_username`) sehingga nomor tanda anggota tidak hilang.
 *
 * Dua langkah penting berurutan:
 *   1. Perlebar kolom `username` dari varchar(30) menjadi varchar(255),
 *      karena alamat email umumnya lebih panjang dari 30 karakter.
 *   2. Baru setelah itu isi ulang username dari email.
 *
 * akun tanpa email (mis. anggota yang dibuat lewat form anggota Feather)
 * tidak dapat dimigrasikan dan sengaja tetap memakai username lamanya.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username', 255)->change();
        });

        $migrated = 0;
        $skipped = 0;

        DB::table('users')
            ->whereNotNull('email')
            ->where('email', '<>', '')
            ->chunkById(200, function ($users) use (&$migrated, &$skipped) {
                foreach ($users as $user) {
                    if ($user->username === $user->email) {
                        continue;
                    }

                    // Jangan menimpa bila email tersebut sudah dipakai sebagai
                    // username akun lain (bisa terjadi bila ada username NTA
                    // yang kebetulan berbentuk email).
                    $taken = DB::table('users')
                        ->where('username', $user->email)
                        ->where('id', '<>', $user->id)
                        ->exists();

                    if ($taken) {
                        $skipped++;

                        continue;
                    }

                    DB::table('users')
                        ->where('id', $user->id)
                        ->update(['username' => $user->email]);

                    $migrated++;
                }
            }, 'id');

        if ($migrated > 0 || $skipped > 0) {
            logger()->info('[migration] username -> email', [
                'migrated' => $migrated,
                'skipped_conflict' => $skipped,
            ]);
        }
    }

    public function down(): void
    {
        // Tidak dapat dibalik: nilai username lama (NTA) sudah hilang, dan
        // memperkecil kolom kembali ke varchar(30) akan memotong alamat email
        // yang lebih panjang. Karena itu rollback sengaja tidak dilakukan
        // daripada merusak data.
    }
};
