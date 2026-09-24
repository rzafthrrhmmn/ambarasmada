<?php

namespace Database\Seeders;

use App\Models\TkkPoint;
use Illuminate\Database\Seeder;

class TkkWajibPenegakSeeder extends Seeder
{
    public function run(): void
    {
        $tkkWajib = [
            'SKK Pertolongan Pertama Pada Kecelakaan (P3K)',
            'SKK Pengatur Rumah',
            'SKK Pengamat',
            'SKK Juru Masak',
            'SKK Berkemah',
            'SKK Penabung',
            'SKK Penjahit',
            'SKK Juru Kebun',
            'SKK Pengaman Kampung',
            'SKK Gerak Jalan',
        ];

        $levels = ['Tingkat 1', 'Tingkat 2', 'Tingkat 3'];

        foreach ($tkkWajib as $nama) {
            foreach ($levels as $level) {
                $fullName = "{$nama} - {$level}";
                TkkPoint::updateOrCreate(
                    ['nama' => $fullName],
                    [
                        'slug' => str()->slug($fullName),
                        'deskripsi' => "TKK Wajib Penegak: {$nama} pada {$level}",
                        'is_active' => true,
                    ]
                );
            }
        }

        $this->command->info('TKK Wajib Penegak seeded successfully. '.(count($tkkWajib) * count($levels)).' points created.');
    }
}
