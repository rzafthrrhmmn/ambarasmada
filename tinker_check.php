<?php

use App\Models\Member;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

echo 'Members count: '.Member::count()."\n";
echo 'Users count: '.User::count()."\n";
echo "Roles breakdown:\n";
foreach (User::selectRaw('role, count(*) as c')->groupBy('role')->get() as $r) {
    echo "  {$r->role}: {$r->c}\n";
}
echo "Sample members:\n";
Member::with('user')->take(5)->get()->each(function ($m) {
    echo "  id={$m->id} nama={$m->nama_lengkap} user_id={$m->user_id} role=".($m->user?->role ?? 'NULL')."\n";
});
