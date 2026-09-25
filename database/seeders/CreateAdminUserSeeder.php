<?php

namespace Database\Seeders;

use App\Models\Ambalan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateAdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = 'abcdwxyz06@gmail.com';
        
        if (User::where('email', $email)->exists()) {
            $this->command->info("Admin user with email {$email} already exists.");
            return;
        }

        $prefix = (string) config('app.gudep_prefix', '31082008');
        $latestAngkatan = \App\Models\Angkatan::query()
            ->where('is_active', true)
            ->where('is_current', true)
            ->first() ?? \App\Models\Angkatan::query()
            ->where('is_active', true)
            ->orderByDesc('nomor')
            ->first();

        $angkatanNomor = $latestAngkatan?->nomor ?? '001';
        $nextUrut = (int) User::where('username', 'like', "{$prefix}.{$angkatanNomor}.%")->count() + 1;
        $formattedUrut = str_pad((string) $nextUrut, 3, '0', STR_PAD_LEFT);
        $username = sprintf('%s.%s.%s', $prefix, $angkatanNomor, $formattedUrut);

        $user = User::create([
            'username' => $username,
            'name' => 'Administrator',
            'email' => $email,
            'password' => Hash::make('password123'), // Default password, user should change
            'role' => 'Admin',
            'is_active' => true,
            'status' => 'approved', // Skip pending approval for admin
            'email_verified_at' => now(), // Skip email verification for admin
        ]);

        $ambalan = Ambalan::first();
        if ($ambalan) {
            Member::create([
                'ambalan_id' => $ambalan->id,
                'user_id' => $user->id,
                'nta' => $username,
                'angkatan' => $angkatanNomor,
                'nomor_urut' => (int) $formattedUrut,
                'nta_username' => $username,
                'nama_lengkap' => 'Administrator',
                'kelas' => '-',
                'tingkatan' => 'Admin',
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ]);
        }

        $this->command->info("Admin user created successfully!");
        $this->command->info("Email: {$email}");
        $this->command->info("Username: {$username}");
        $this->command->info("Password: password123 (please change after login)");
        $this->command->info("Role: Admin");
        $this->command->info("Status: approved (email verified)");
    }
}
