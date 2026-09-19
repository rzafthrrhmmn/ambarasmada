<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\Ambalan;
use App\Models\Angkatan;
use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

foreach (range(2009, 2026) as $year) {
    Angkatan::updateOrCreate(
        ['angkatan' => (string) $year],
        [
            'nomor' => str_pad((string) ($year - 2008), 3, '0', STR_PAD_LEFT),
            'nama' => "Angkatan {$year}",
            'is_active' => $year === 2025,
        ]
    );
}

$ambalan = Ambalan::firstOrCreate(
    ['kode' => 'SMAN2MAROS'],
    ['nama' => 'Ambalan UPT SMAN 2 Maros', 'alamat' => 'Maros, Sulawesi Selatan', 'status' => 'Aktif']
);

User::updateOrCreate(
    ['username' => 'pembina'],
    [
        'name' => 'Pembina Ambalan',
        'email' => 'pembina@ambalan.test',
        'password' => Hash::make('password'),
        'role' => 'Pembina',
        'is_active' => true,
        'status' => 'approved',
        'email_verified_at' => now(),
    ]
);

$pembina = User::where('username', 'pembina')->first();
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
    ]
);

echo "Pembina restored\n";
echo "Username: pembina\n";
echo "Password: password\n";
