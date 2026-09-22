<?php

error_log('[VERCEL-START] api/index.php started, PHP version: '.PHP_VERSION);
error_log('[VERCEL-START] Current dir: '.getcwd());
error_log('[VERCEL-START] APP_KEY from env: '.(getenv('APP_KEY') ?: 'NOT SET'));
error_log('[VERCEL-START] DB_HOST from env: '.(getenv('DB_HOST') ?: 'NOT SET'));
error_log('[VERCEL-START] DB_PASSWORD from env: '.(getenv('DB_PASSWORD') ? 'SET' : 'NOT SET'));
error_log('[VERCEL-START] DB_PORT from env: '.(getenv('DB_PORT') ?: 'NOT SET'));
error_log('[VERCEL-START] DB_USERNAME from env: '.(getenv('DB_USERNAME') ?: 'NOT SET'));
error_log('[VERCEL-START] DB_DATABASE from env: '.(getenv('DB_DATABASE') ?: 'NOT SET'));

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Filesystem\FilesystemServiceProvider;

define('LARAVEL_START', microtime(true));

$storagePath = '/tmp/storage';
$directories = [
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
];
foreach ($directories as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

putenv('APP_STORAGE='.$storagePath);
putenv('VIEW_COMPILED_PATH='.$storagePath.'/framework/views');
putenv('SESSION_DRIVER=cookie');
putenv('CACHE_DRIVER=array');
putenv('CACHE_STORE=array');
putenv('DB_CONNECTION=pgsql');
putenv('DB_SSLMODE=require');
putenv('QUEUE_CONNECTION=sync');

$_ENV = array_merge($_ENV, [
    'SESSION_DRIVER' => 'cookie',
    'CACHE_DRIVER' => 'array',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'pgsql',
    'DB_SSLMODE' => 'require',
    'QUEUE_CONNECTION' => 'sync',
]);
$_SERVER = array_merge($_SERVER, $_ENV);

require __DIR__.'/../vendor/autoload.php';
error_log('[VERCEL-DEBUG] Autoload loaded');
$app = require_once __DIR__.'/../bootstrap/app.php';
error_log('[VERCEL-DEBUG] App bootstrapped');
$app->useStoragePath($storagePath);

// Register filesystem provider manually (critical for eventsAreCached())
$app->register(FilesystemServiceProvider::class, force: true);
error_log('[VERCEL-DEBUG] Filesystem provider registered');

// Remove cached events to prevent boot issues
$cachedEvents = $storagePath.'/../bootstrap/cache/events.php';
if (file_exists($cachedEvents)) unlink($cachedEvents);

error_log('[VERCEL-DEBUG] Booting Laravel app...');
error_log('[VERCEL-DEBUG] Storage path: '.$storagePath);

$request = Request::capture();
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);
error_log('[VERCEL-DEBUG] Kernel created, class: '.get_class($kernel));

try {
    $response = $kernel->handle($request);
    error_log('[VERCEL-DEBUG] Request handled, status: '.$response->getStatusCode());
    error_log('[VERCEL-DEBUG] Response content: '.$response->getContent());
    error_log('[VERCEL-DEBUG] Response headers: '.json_encode($response->headers->all()));
} catch (\Throwable $e) {
    error_log('[VERCEL-EXCEPTION] Class: '.get_class($e));
    error_log('[VERCEL-EXCEPTION] Message: '.$e->getMessage());
    error_log('[VERCEL-EXCEPTION] File: '.$e->getFile().':'.$e->getLine());
    error_log('[VERCEL-EXCEPTION] Trace: '.$e->getTraceAsString());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode([
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $kernel->terminate($request, null);
    exit(500);
}

if (!headers_sent()) $response->send();
else {
    error_log('[VERCEL-DEBUG] Headers already sent, echoing content: '.substr($response->getContent(), 0, 500));
    echo $response->getContent();
}

$kernel->terminate($request, $response);
