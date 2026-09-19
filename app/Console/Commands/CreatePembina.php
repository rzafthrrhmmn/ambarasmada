<?php

namespace App\Console\Commands;

use App\Models\Ambalan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreatePembina extends Command
{
    protected $signature = 'admin:create-pembina';

    protected $description = 'Create a Pembina admin user permanently with profile support';

    public function handle(): int
    {
        $ambalan = Ambalan::firstOrCreate(
            ['kode' => 'SMAN2MAROS'],
            [
                'nama' => 'Ambalan UPT SMAN 2 Maros',
                'alamat' => 'Maros, Sulawesi Selatan',
                'status' => 'Aktif',
            ],
        );

        $user = User::updateOrCreate(
            ['username' => 'pembina'],
            [
                'name' => 'Pembina Ambalan',
                'email' => 'pembina@ambalan.test',
                'password' => Hash::make('password'),
                'role' => 'Pembina',
                'is_active' => true,
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        Member::updateOrCreate(
            ['user_id' => $user->id],
            [
                'ambalan_id' => $ambalan->id,
                'nta' => '-',
                'angkatan' => '-',
                'nomor_urut' => 0,
                'nta_username' => '-',
                'nama_lengkap' => 'Pembina Ambalan',
                'kelas' => '-',
                'tingkatan' => '-',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '081234567890',
            ],
        );

        $this->info('Akun Pembina permanen tersedia.');
        $this->info('Username: pembina');
        $this->info('Password: password');

        return self::SUCCESS;
    }
}
