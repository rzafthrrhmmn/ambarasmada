<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Buat direktori temporary yang dibutuhkan Laravel di /tmp Vercel
$storagePath = '/tmp/storage';
$directories = [
    $storagePath . '/app/public',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/cache/routes',
    $storagePath . '/framework/sessions',
    $storagePath . '/framework/views',
    $storagePath . '/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $dir) {
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// 2. Set environment paths agar tidak menulis ke filesystem read-only Vercel
putenv('APP_STORAGE=' . $storagePath);
putenv('VIEW_COMPILED_PATH=' . $storagePath . '/framework/views');
putenv('APP_SERVICES_CACHE=' . $storagePath . '/../bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE=' . $storagePath . '/../bootstrap/cache/packages.php');
putenv('APP_CONFIG_CACHE=' . $storagePath . '/../bootstrap/cache/config.php');
putenv('APP_ROUTES_CACHE=' . $storagePath . '/../bootstrap/cache/routes.php');
putenv('APP_EVENTS_CACHE=' . $storagePath . '/../bootstrap/cache/events.php');

// 3. Pastikan driver tidak pernah string kosong yang menyebabkan createDriver crash
$sessionDriver = trim((string) getenv('SESSION_DRIVER')) ?: 'file';
$cacheStore    = trim((string) (getenv('CACHE_STORE') ?: getenv('CACHE_DRIVER'))) ?: 'file';
$dbConnection  = trim((string) getenv('DB_CONNECTION')) ?: 'mysql';
$queueConn     = trim((string) getenv('QUEUE_CONNECTION')) ?: 'sync';

putenv('SESSION_DRIVER=' . $sessionDriver);
putenv('CACHE_STORE=' . $cacheStore);
putenv('CACHE_DRIVER=' . $cacheStore);
putenv('DB_CONNECTION=' . $dbConnection);
putenv('QUEUE_CONNECTION=' . $queueConn);

$_ENV['SESSION_DRIVER']     = $_SERVER['SESSION_DRIVER']     = $sessionDriver;
$_ENV['CACHE_STORE']        = $_SERVER['CACHE_STORE']        = $cacheStore;
$_ENV['CACHE_DRIVER']       = $_SERVER['CACHE_DRIVER']       = $cacheStore;
$_ENV['DB_CONNECTION']      = $_SERVER['DB_CONNECTION']      = $dbConnection;
$_ENV['QUEUE_CONNECTION']   = $_SERVER['QUEUE_CONNECTION']   = $queueConn;

// 4. Autoload vendor & bootstrap Laravel
require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 5. Injeksi konfigurasi ke container sebelum Service Provider / Middleware boot
$app->booting(function () use ($sessionDriver, $cacheStore, $dbConnection, $queueConn) {
    config([
        'session.driver'   => $sessionDriver,
        'cache.default'    => $cacheStore,
        'database.default' => $dbConnection,
        'queue.default'    => $queueConn,
    ]);
});

// 6. Bind storage path ke /tmp & proses request
$app->useStoragePath($storagePath);

$app->handleRequest(Request::capture());
