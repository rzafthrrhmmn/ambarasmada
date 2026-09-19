<?php

namespace Database\Seeders;

use App\Models\Ambalan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoUsersSeeder extends Seeder
{
    public function run(): void
    {
        $ambalan = Ambalan::where('kode', 'SMAN2MAROS')->first() ?? Ambalan::create([
            'kode' => 'SMAN2MAROS',
            'nama' => 'Ambalan UPT SMAN 2 Maros',
            'alamat' => 'Maros, Sulawesi Selatan',
            'status' => 'Aktif',
        ]);

        $pembina = User::updateOrCreate(
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
            ['user_id' => $pembina->id],
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
                'no_hp' => '-',
            ],
        );
    }
}
