<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class OfficialPramukaPenegakSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(SkuPenegakPointSeeder::class);
        $this->call(TkkWajibPenegakSeeder::class);
    }
}
