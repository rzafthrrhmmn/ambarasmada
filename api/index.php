<?php

error_log('[VERCEL-START] api/index.php started, PHP version: ' . PHP_VERSION);
error_log('[VERCEL-START] Current dir: ' . getcwd());

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$storagePath = '/tmp/storage';
$directories = [
    $storagePath . '/app/public',
    $storagePath . '/framework/cache/data',
    $storagePath . '/framework/sessions',
    $storagePath . '/framework/views',
    $storagePath . '/logs',
];
foreach ($directories as $dir) {
    if (!is_dir($dir)) mkdir($dir, 0755, true);
}

// Remove cached events to prevent boot issues
$cachedEvents = __DIR__ . '/../bootstrap/cache/events.php';
if (file_exists($cachedEvents)) unlink($cachedEvents);

// Remove cached config to force fresh load from config files
$cachedConfig = __DIR__ . '/../bootstrap/cache/config.php';
if (file_exists($cachedConfig)) unlink($cachedConfig);

// Remove cached services
$cachedServices = __DIR__ . '/../bootstrap/cache/services.php';
if (file_exists($cachedServices)) unlink($cachedServices);

// Remove cached packages
$cachedPackages = __DIR__ . '/../bootstrap/cache/packages.php';
if (file_exists($cachedPackages)) unlink($cachedPackages);

// Remove cached routes
$cachedRoutes = __DIR__ . '/../bootstrap/cache/routes-v7.php';
if (file_exists($cachedRoutes)) unlink($cachedRoutes);

putenv('APP_STORAGE=' . $storagePath);
putenv('SESSION_DRIVER=cookie');
putenv('CACHE_DRIVER=array');
putenv('CACHE_STORE=array');
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

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($storagePath);

error_log('[VERCEL-DEBUG] App bootstrapped with storage at: ' . $storagePath);

$request = Request::capture();
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);

try {
    $response = $kernel->handle($request);
    error_log('[VERCEL-DEBUG] Request handled, status: ' . $response->getStatusCode());
} catch (\Throwable $e) {
    error_log('[VERCEL-EXCEPTION] Class: ' . get_class($e));
    error_log('[VERCEL-EXCEPTION] Message: ' . $e->getMessage());
    error_log('[VERCEL-EXCEPTION] File: ' . $e->getMessage());
    error_log('[VERCEL-EXCEPTION] Trace: ' . substr($e->getTraceAsString(), 0, 2000));

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
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $kernel->terminate($request, null);
    exit(500);
}

if (!headers_sent()) {
    $response->send();
} else {
    error_log('[VERCEL-DEBUG] Headers already sent, echoing content: ' . substr($response->getContent(), 0, 500));
    echo $response->getContent();
}

$kernel->terminate($request, $response);
