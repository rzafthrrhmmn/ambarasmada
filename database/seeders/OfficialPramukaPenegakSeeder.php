<?php

namespace Database\Seeders;

use Illuminate\Database\Seeders\DatabaseSeeder as BaseSeeder;

class OfficialPramukaPenegakSeeder extends BaseSeeder
{
    public function run(): void
    {
        $this->call(SkuPenegakPointSeeder::class);
        $this->call(TkkWajibPenegakSeeder::class);
    }
}
