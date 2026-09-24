<?php

namespace Database\Seeders;

use App\Models\Ambalan;
use App\Models\Angkatan;
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

        $currentAngkatan = Angkatan::where('is_current', true)->first()
            ?? Angkatan::where('is_active', true)->orderByDesc('nomor')->first();

        $angkatanNomor = $currentAngkatan?->nomor ?? '018';

        // Pembina
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

        // Admin
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Ambalan',
                'email' => 'admin@ambalan.test',
                'password' => Hash::make('password'),
                'role' => 'Admin',
                'is_active' => true,
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        Member::updateOrCreate(
            ['user_id' => $admin->id],
            [
                'ambalan_id' => $ambalan->id,
                'nta' => '31082008.'.$angkatanNomor.'.001',
                'angkatan' => $angkatanNomor,
                'nomor_urut' => 1,
                'nta_username' => '31082008.'.$angkatanNomor.'.001',
                'nama_lengkap' => 'Admin Ambalan',
                'kelas' => '-',
                'tingkatan' => 'Laksana',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ],
        );

        // Pengurus - Pradana
        $pradana = User::updateOrCreate(
            ['username' => 'pradana'],
            [
                'name' => 'Ketua Pradana',
                'email' => 'pradana@ambalan.test',
                'password' => Hash::make('password'),
                'role' => 'Pengurus',
                'is_active' => true,
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        Member::updateOrCreate(
            ['user_id' => $pradana->id],
            [
                'ambalan_id' => $ambalan->id,
                'nta' => '31082008.'.$angkatanNomor.'.002',
                'angkatan' => $angkatanNomor,
                'nomor_urut' => 2,
                'nta_username' => '31082008.'.$angkatanNomor.'.002',
                'nama_lengkap' => 'Ketua Pradana',
                'kelas' => 'XII',
                'tingkatan' => 'Laksana',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ],
        );

        // Pengurus - Bendahara
        $bendahara = User::updateOrCreate(
            ['username' => 'bendahara'],
            [
                'name' => 'Bendahara',
                'email' => 'bendahara@ambalan.test',
                'password' => Hash::make('password'),
                'role' => 'Pengurus',
                'is_active' => true,
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        Member::updateOrCreate(
            ['user_id' => $bendahara->id],
            [
                'ambalan_id' => $ambalan->id,
                'nta' => '31082008.'.$angkatanNomor.'.003',
                'angkatan' => $angkatanNomor,
                'nomor_urut' => 3,
                'nta_username' => '31082008.'.$angkatanNomor.'.003',
                'nama_lengkap' => 'Bendahara',
                'kelas' => 'XI',
                'tingkatan' => 'Bantara',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ],
        );

        // Anggota
        $anggota = User::updateOrCreate(
            ['username' => 'anggota'],
            [
                'name' => 'Anggota Demo',
                'email' => 'anggota@ambalan.test',
                'password' => Hash::make('password'),
                'role' => 'Anggota',
                'is_active' => true,
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        Member::updateOrCreate(
            ['user_id' => $anggota->id],
            [
                'ambalan_id' => $ambalan->id,
                'nta' => '31082008.'.$angkatanNomor.'.004',
                'angkatan' => $angkatanNomor,
                'nomor_urut' => 4,
                'nta_username' => '31082008.'.$angkatanNomor.'.004',
                'nama_lengkap' => 'Anggota Demo',
                'kelas' => 'X',
                'tingkatan' => 'Calon',
                'tahun_lulus' => null,
                'status_aktif' => 'Aktif',
                'no_hp' => '-',
            ],
        );

        // Alumni
        $alumni = User::updateOrCreate(
            ['username' => 'alumni'],
            [
                'name' => 'Alumni Demo',
                'email' => 'alumni@ambalan.test',
                'password' => Hash::make('password'),
                'role' => 'Alumni',
                'is_active' => true,
                'status' => 'approved',
                'email_verified_at' => now(),
            ],
        );

        Member::updateOrCreate(
            ['user_id' => $alumni->id],
            [
                'ambalan_id' => $ambalan->id,
                'nta' => '31082008.017.001',
                'angkatan' => '017',
                'nomor_urut' => 1,
                'nta_username' => '31082008.017.001',
                'nama_lengkap' => 'Alumni Demo',
                'kelas' => '-',
                'tingkatan' => 'Alumni',
                'tahun_lulus' => 2023,
                'status_aktif' => 'Alumni',
                'no_hp' => '-',
            ],
        );
    }
}
