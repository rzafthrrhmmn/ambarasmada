<?php
require __DIR__."/vendor/autoload.php";
$app = require __DIR__."/bootstrap/app.php";
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== Creating test data for all roles ===" . PHP_EOL;

// 1. Create ambalan if not exists
$ambalan = DB::table("ambalans")->first();
if (!$ambalan) {
    $ambalanId = DB::table("ambalans")->insertGetId([
        "nama" => "Ambalan UPT SMAN 2 Maros",
        "kode" => "SMAN2MAROS",
        "status" => "Aktif",
        "logo_path" => null,
        "created_at" => now()->toDateTimeString(),
        "updated_at" => now()->toDateTimeString(),
    ]);
    echo "Created ambalan: ID=$ambalanId" . PHP_EOL;
    $ambalan = DB::table("ambalans")->find($ambalanId);
} else {
    echo "Ambalan found: ID=" . $ambalan->id . " - " . $ambalan->nama . PHP_EOL;
}

// 2. Ensure test users exist with correct credentials
$testUsers = [
    ["username" => "admin", "role" => "Admin", "password" => "adminpass"],
    ["username" => "pembfis", "role" => "Pembina", "password" => "pembinapass"],
    ["username" => "pengg", "role" => "Pengurus", "password" => "penguruspass"],
    ["username" => "anggo", "role" => "Anggota", "password" => "anggomapass"],
    ["username" => "alumni1", "role" => "Alumni", "password" => "alumnipass"],
];

foreach ($testUsers as $tu) {
    // Check if user exists
    $user = DB::table("users")->where("username", $tu["username"])->first();
    
    if (!$user) {
        // Check by role
        $existing = DB::table("users")->where("role", $tu["role"])->first();
        if ($existing) {
            // Update existing user
            DB::table("users")->where("id", $existing->id)->update([
                "username" => $tu["username"],
                "role" => $tu["role"],
                "is_active" => true,
                "status" => "approved",
                "password" => Hash::make($tu["password"]),
            ]);
            $user = DB::table("users")->find($existing->id);
            echo $tu["role"] . ": Updated existing user to " . $tu["username"] . " (ID: " . $user->id . ")" . PHP_EOL;
        } else {
            // Create new
            $userId = DB::table("users")->insertGetId([
                "username" => $tu["username"],
                "name" => ucfirst($tu["role"]) . " User",
                "email" => $tu["username"] . "@ambara.local",
                "role" => $tu["role"],
                "is_active" => true,
                "status" => "approved",
                "password" => Hash::make($tu["password"]),
                "created_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString(),
            ]);
            $user = DB::table("users")->find($userId);
            echo $tu["role"] . ": Created new user " . $tu["username"] . " (ID: " . $userId . ")" . PHP_EOL;
        }
    } else {
        // Update password
        DB::table("users")->where("id", $user->id)->update([
            "role" => $tu["role"],
            "is_active" => true,
            "status" => "approved",
            "password" => Hash::make($tu["password"]),
        ]);
        echo $tu["role"] . ": User already exists, updated " . $tu["username"] . " (ID: " . $user->id . ")" . PHP_EOL;
    }
    
    // Create member record
    $memberExists = DB::table("members")->where("user_id", $user->id)->exists();
    if (!$memberExists) {
        try {
            DB::table("members")->insert([
                "user_id" => $user->id,
                "ambalan_id" => $ambalan->id,
                "nama_lengkap" => ucfirst($tu["role"]) . " User",
                "kelas" => "X",
                "tingkatan" => "Penggalangan",
                "status_aktif" => $tu["role"] === "Alumni" ? "Alumni" : "Aktif",
                "nta_username" => $tu["username"],
                "created_at" => now()->toDateTimeString(),
                "updated_at" => now()->toDateTimeString(),
            ]);
            echo "  -> Member record created" . PHP_EOL;
        } catch (Exception $e) {
            // Try with different column names
            $cols = DB::getSchemaBuilder()->getColumnListing("members");
            echo "  -> Member creation error. Columns: " . implode(", ", $cols) . PHP_EOL;
            echo "  -> Error: " . substr($e->getMessage(), 0, 200) . PHP_EOL;
        }
    } else {
        echo "  -> Member record exists" . PHP_EOL;
    }
}

// Final verification
echo PHP_EOL . "=== Final Verification ===" . PHP_EOL;
foreach ($testUsers as $tu) {
    $user = DB::table("users")->where("username", $tu["username"])->first();
    $member = $user ? DB::table("members")->where("user_id", $user->id)->first() : null;
    
    echo $tu["role"] . ": " . PHP_EOL;
    echo "  username: " . $tu["username"] . PHP_EOL;
    echo "  password: " . $tu["password"] . PHP_EOL;
    echo "  id: " . ($user ? $user->id : "NOT FOUND") . PHP_EOL;
    echo "  role: " . ($user ? $user->role : "N/A") . PHP_EOL;
    echo "  status: " . ($user ? $user->status : "N/A") . PHP_EOL;
    echo "  is_active: " . ($user ? $user->is_active : "N/A") . PHP_EOL;
    echo "  password_ok: " . ($user ? (Hash::check($tu["password"], $user->password) ? "YES" : "NO") : "N/A") . PHP_EOL;
    echo "  member_id: " . ($member ? $member->id : "NULL") . PHP_EOL;
    echo "  status_aktif: " . ($member ? $member->status_aktif : "NULL") . PHP_EOL;
    echo PHP_EOL;
}
