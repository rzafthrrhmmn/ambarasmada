<?php
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;

echo "=== Creating member records ===" . PHP_EOL;

// Get ambalan
$ambalan = DB::table("ambalans")->first();
echo "Ambalan: ID=" . $ambalan->id . ", " . $ambalan->nama . PHP_EOL;

// Test users data
$users = [
    ["id" => 1, "role" => "Admin", "nama" => "Admin User"],
    ["id" => 2, "role" => "Pembina", "nama" => "Pembina User"],
    ["id" => 3, "role" => "Pengurus", "nama" => "Pengurus User"],
    ["id" => 4, "role" => "Anggota", "nama" => "Anggota User"],
    ["id" => 5, "role" => "Alumni", "nama" => "Alumni User"],
];

// Find angkatan if available, else use default
$angkatan = DB::table("angkatans")->orderBy("id")->first();
$angkatanValue = $angkatan ? $angkatan->id : 1;
echo "Angkatan: " . ($angkatan ? $angkatan->nama : "Default 1") . PHP_EOL;

foreach ($users as $u) {
    // Check if member exists
    $member = DB::table("members")->where("user_id", $u["id"])->first();
    
    if ($member) {
        // Update existing
        DB::table("members")->where("user_id", $u["id"])->update([
            "nama_lengkap" => $u["nama"],
            "status_aktif" => $u["role"] === "Alumni" ? "Alumni" : "Aktif",
            "updated_at" => now()->toDateTimeString(),
        ]);
        echo $u["role"] . ": Member updated (ID: " . $member->id . ")" . PHP_EOL;
    } else {
        // Insert with all required fields
        try {
            $memberId = DB::table("members")->insertGetId([
                "user_id" => $u["id"],
                "ambalan_id" => $ambalan->id,
                "nama_lengkap" => $u["nama"],
                "kelas" => "X",
                "tingkatan" => "Penggalangan",
                "angkatan" => $angkatanValue,
                "nomor_urut" => 100 + $u["id"],
                "nta_username" => $u["nama"],
                "status_aktif" => $u["role"] === "Alumni" ? "Alumni" : "Aktif",
                "created_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString(),
            ]);
            echo $u["role"] . ": Member created (ID: " . $memberId . ")" . PHP_EOL;
        } catch (Exception $e) {
            echo $u["role"] . " ERROR: " . substr($e->getMessage(), 0, 300) . PHP_EOL;
            
            // Show the actual error without column hint
            $msg = $e->getMessage();
            if (strpos($msg, "not-null constraint") !== false) {
                // Extract column name
                preg_match('/column "([^"]+)"/', $msg, $matches);
                if ($matches) {
                    echo "  -> Missing required column: " . $matches[1] . PHP_EOL;
                }
            }
        }
    }
}

// Final check
echo PHP_EOL . "=== Final member/user check ===" . PHP_EOL;
foreach ($users as $u) {
    $user = DB::table("users")->find($u["id"]);
    $member = DB::table("members")->where("user_id", $u["id"])->first();
    
    echo $u["role"] . ": " . PHP_EOL;
    echo "  User: " . ($user ? "exists" : "NOT FOUND") . PHP_EOL;
    echo "  Member: " . ($member ? "exists (ID: " . $member->id . ")" : "NULL") . PHP_EOL;
    if ($member) {
        echo "  status_aktif: " . $member->status_aktif . PHP_EOL;
    }
}

// Print final credentials summary
echo PHP_EOL . "=== LOGIN CREDENTIALS ===" . PHP_EOL;
echo "Admin    : username=admin,     password=adminpass     -> /dashboard" . PHP_EOL;
echo "Pembina  : username=pembfis,   password=pembinapass   -> /dashboard" . PHP_EOL;
echo "Pengurus : username=pengg,     password=penguruspass  -> /dashboard" . PHP_EOL;
echo "Anggota  : username=anggo,     password=anggomapass   -> /dashboard" . PHP_EOL;
echo "Alumni   : username=alumni1,   password=alumnipass    -> /alumni/dashboard" . PHP_EOL;
