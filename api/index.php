<?php

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
putenv('QUEUE_CONNECTION=sync');
$_ENV = array_merge($_ENV, [
    'SESSION_DRIVER' => 'cookie',
    'CACHE_DRIVER' => 'array',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'pgsql',
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
    error_log('[VERCEL-DEBUG] Kernel created');

    try {
        $response = $kernel->handle($request);
        error_log('[VERCEL-DEBUG] Request handled, status: '.$response->getStatusCode());
    } catch (\Throwable $e) {
    error_log('[VERCEL-APP-ERROR] '.$e->getMessage());
    error_log('[VERCEL-APP-ERROR] '.$e->getFile().':'.$e->getLine());
    error_log('[VERCEL-APP-ERROR] '.$e->getTraceAsString());

    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode([
        'exception' => get_class($e),
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    $kernel->terminate($request, null);
    exit(500);
}

if (!headers_sent()) $response->send();
else echo $response->getContent();

$kernel->terminate($request, $response);
