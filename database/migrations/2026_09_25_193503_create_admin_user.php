<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $email = 'abcdwxyz06@gmail.com';
        
        if (DB::table('users')->where('email', $email)->exists()) {
            return;
        }

        $prefix = config('app.gudep_prefix', '31082008');
        $angkatanNomor = '001';
        $nextUrut = 1;
        $formattedUrut = str_pad((string) $nextUrut, 3, '0', STR_PAD_LEFT);
        $username = sprintf('%s.%s.%s', $prefix, $angkatanNomor, $formattedUrut);

        $userId = DB::table('users')->insertGetId([
            'username' => $username,
            'name' => 'Administrator',
            'email' => $email,
            'password' => password_hash('password123', PASSWORD_BCRYPT),
            'role' => 'Admin',
            'is_active' => true,
            'status' => 'approved',
            'email_verified_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $ambalan = DB::table('ambalans')->first();
        if ($ambalan) {
            DB::table('members')->insert([
                'ambalan_id' => $ambalan->id,
                'user_id' => $userId,
                'nta' => $username,
                'angkatan' => $angkatanNomor,
                'nomor_urut' => (int) $formattedUrut,
                'nta_username' => $username,
                'nama_lengkap' => 'Administrator',
                'kelas' => '-',
                'tingkatan' => 'Admin',
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'abcdwxyz06@gmail.com')->delete();
        DB::table('members')->where('nta_username', '31082008.001.001')->delete();
    }
};