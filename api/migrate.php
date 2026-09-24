<?php
define("LARAVEL_START", microtime(true));
require __DIR__."/../vendor/autoload.php";
$app = require_once __DIR__."/../bootstrap/app.php";
use Illuminate\Contracts\Console\Kernel;
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();
$exitCode = $kernel->call("migrate", ["--force" => true, "--path" => "database/migrations"]);
$output = $kernel->output();
header("Content-Type: application/json");
echo json_encode([
    "exit_code" => $exitCode,
    "output" => $output,
    "message" => $exitCode === 0 ? "Migration successful" : "Migration failed"
]) . "\n";
exit;
