<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Create angkatan if not exists
        $angkatan = DB::table('angkatans')
            ->where('nomor', '001')
            ->first();

        if (! $angkatan) {
            $angkatanId = DB::table('angkatans')->insertGetId([
                'angkatan' => '001',
                'nama' => 'Angkatan 1',
                'nomor' => '001',
                'is_active' => true,
                'is_current' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            $angkatanId = $angkatan->id;
            // Update to ensure it's active and current
            DB::table('angkatans')
                ->where('id', $angkatanId)
                ->update([
                    'is_active' => true,
                    'is_current' => true,
                    'updated_at' => now(),
                ]);
        }

        // Create admin user with verified password hash
        $email = 'abcdxwyz06@gmail.com';

        if (! DB::table('users')->where('email', $email)->exists()) {
            $prefix = config('app.gudep_prefix', '31082008');
            $username = '31082008.001.001';

            // Pre-verified bcrypt hash for 'password123'
            $passwordHash = '$2y$12$Gd7xPA6sBDDyQSx.9.cZ5e.Jj9cGQwcbhu6D2ncN4ednq5PfgSrEW';

            $userId = DB::table('users')->insertGetId([
                'username' => $username,
                'name' => 'Administrator',
                'email' => $email,
                'password' => $passwordHash,
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
                    'angkatan' => '001',
                    'nomor_urut' => 1,
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
    }

    public function down(): void
    {
        DB::table('users')->where('email', 'abcdxwyz06@gmail.com')->delete();
        DB::table('members')->where('nta_username', '31082008.001.001')->delete();
        DB::table('angkatans')->where('nomor', '001')->delete();
    }
};
