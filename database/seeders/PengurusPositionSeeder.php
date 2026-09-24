<?php

namespace Database\Seeders;

use App\Models\PengurusPosition;
use Illuminate\Database\Seeder;

class PengurusPositionSeeder extends Seeder
{
    public function run(): void
    {
        $positions = [
            ['name' => 'Pradana Putra', 'code' => 'pradana_putra', 'description' => 'Pradana Pengurus Putra', 'is_putra' => true],
            ['name' => 'Pradana Putri', 'code' => 'pradana_putri', 'description' => 'Pradana Pengurus Putri', 'is_putra' => false],
            ['name' => 'Pemangku Adat Putra', 'code' => 'pemangku_adat_putra', 'description' => 'Pemangku Adat Pengurus Putra', 'is_putra' => true],
            ['name' => 'Pemangku Adat Putri', 'code' => 'pemangku_adat_putri', 'description' => 'Pemangku Adat Pengurus Putri', 'is_putra' => false],
            ['name' => 'Kerani Putra', 'code' => 'kerani_putra', 'description' => 'Kerani Pengurus Putra', 'is_putra' => true],
            ['name' => 'Kerani Putri', 'code' => 'kerani_putri', 'description' => 'Kerani Pengurus Putri', 'is_putra' => false],
            ['name' => 'Juru Uang Putra', 'code' => 'juru_uang_putra', 'description' => 'Juru Uang Pengurus Putra', 'is_putra' => true],
            ['name' => 'Juru Uang Putri', 'code' => 'juru_uang_putri', 'description' => 'Juru Uang Pengurus Putri', 'is_putra' => false],
            ['name' => 'Pembantu Pembina Putra', 'code' => 'pembantu_pembina_putra', 'description' => 'Pembantu Pembina Pengurus Putra', 'is_putra' => true],
            ['name' => 'Pembantu Pembina Putri', 'code' => 'pembantu_pembina_putri', 'description' => 'Pembantu Pembina Pengurus Putri', 'is_putra' => false],
        ];

        foreach ($positions as $position) {
            PengurusPosition::firstOrCreate(['code' => $position['code']], $position);
        }
    }
}
