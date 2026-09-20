<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// 1. Buat struktur folder sementara di /tmp Vercel
$storagePath = '/tmp/storage';
$directories = [
    $storagePath . '/app/public',
    $storagePath . '/framework/cache/data',
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

// 2. Set Environment Variables untuk path temporary
putenv('APP_STORAGE=' . $storagePath);
putenv('VIEW_COMPILED_PATH=' . $storagePath . '/framework/views');
putenv('APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php');
putenv('APP_CONFIG_CACHE=/tmp/bootstrap/cache/config.php');
putenv('APP_ROUTES_CACHE=/tmp/bootstrap/cache/routes.php');
putenv('APP_EVENTS_CACHE=/tmp/bootstrap/cache/events.php');

// 3. Fallback Environment Variables
$fallbacks = [
    'SESSION_DRIVER'   => 'file',
    'CACHE_STORE'      => 'file',
    'CACHE_DRIVER'     => 'file',
    'QUEUE_CONNECTION' => 'sync',
    'DB_CONNECTION'    => 'mysql',
    'APP_URL'          => 'https://ambarasmada.vercel.app',
];

foreach ($fallbacks as $key => $default) {
    $val = getenv($key);
    if ($val === false || $val === '' || $val === null) {
        putenv("{$key}={$default}");
        $_ENV[$key] = $default;
        $_SERVER[$key] = $default;
    } else {
        $_ENV[$key] = $val;
        $_SERVER[$key] = $val;
    }
}

// 4. Load Autoload & Application Bootstrap
require __DIR__ . '/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__ . '/../bootstrap/app.php';

// 5. Hard-fix: Pastikan Config Repository Laravel tidak menyimpan NULL pada Session & Cache driver
$app->booted(function ($app) {
    $sessionDriver = config('session.driver');
    if (empty($sessionDriver)) {
        config(['session.driver' => env('SESSION_DRIVER', 'file') ?: 'file']);
    }

    $cacheStore = config('cache.default');
    if (empty($cacheStore)) {
        config(['cache.default' => env('CACHE_STORE', 'file') ?: 'file']);
    }
});

// 6. Bind Storage Path & Run Request
$app->useStoragePath($storagePath);

$app->handleRequest(Request::capture());