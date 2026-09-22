<?php
// Versi produksi final - api/index.php
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Throwable;

define('LARAVEL_START', microtime(true));

// Vercel serverless environment setup
$storagePath = '/tmp/storage';

// Create required directories
$directories = [
    $storagePath.'/app/public',
    $storagePath.'/framework/cache/data',
    $storagePath.'/framework/cache/routes',
    $storagePath.'/framework/sessions',
    $storagePath.'/framework/views',
    $storagePath.'/logs',
    '/tmp/bootstrap/cache',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}

// Environment safe defaults
$sessionDriver = trim((string) getenv('SESSION_DRIVER')) ?: 'file';
$cacheStore = trim((string) (getenv('CACHE_STORE') ?: getenv('CACHE_DRIVER'))) ?: 'file';
$dbConnection = trim((string) getenv('DB_CONNECTION')) ?: 'pgsql';
$queueConnection = trim((string) getenv('QUEUE_CONNECTION')) ?: 'sync';

// Set environment variables
putenv('APP_STORAGE='.$storagePath);
putenv('VIEW_COMPILED_PATH='.$storagePath.'/framework/views');
putenv('APP_SERVICES_CACHE='.$storagePath.'/../bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE='.$storagePath.'/../bootstrap/cache/packages.php');
putenv('APP_CONFIG_CACHE='.$storagePath.'/../bootstrap/cache/config.php');
putenv('APP_ROUTES_CACHE='.$storagePath.'/../bootstrap/cache/routes.php');
putenv('APP_EVENTS_CACHE='.$storagePath.'/../bootstrap/cache/events.php');
putenv('SESSION_DRIVER='.$sessionDriver);
putenv('CACHE_STORE='.$cacheStore);
putenv('CACHE_DRIVER='.$cacheStore);
putenv('DB_CONNECTION='.$dbConnection);
putenv('QUEUE_CONNECTION='.$queueConnection);

$_ENV['SESSION_DRIVER'] = $_SERVER['SESSION_DRIVER'] = $sessionDriver;
$_ENV['CACHE_STORE'] = $_SERVER['CACHE_STORE'] = $cacheStore;
$_ENV['CACHE_DRIVER'] = $_SERVER['CACHE_DRIVER'] = $cacheStore;
$_ENV['DB_CONNECTION'] = $_SERVER['DB_CONNECTION'] = $dbConnection;
$_ENV['QUEUE_CONNECTION'] = $_SERVER['QUEUE_CONNECTION'] = $queueConnection;

// Bootstrap
require __DIR__.'/../vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($storagePath);

// Override config before boot
$app->booting(function () use ($sessionDriver, $cacheStore, $dbConnection, $queueConnection) {
    config([
        'session.driver' => $sessionDriver,
        'cache.default' => $cacheStore,
        'database.default' => $dbConnection,
        'queue.default' => $queueConnection,
    ]);
});

// Handle request via HTTP Kernel (gives full control over response)
$request = Request::capture();
$kernel = $app->make(Kernel::class);

try {
    $response = $kernel->handle($request);
} catch (Throwable $e) {
    // Log exception
    error_log('[VERCEL-APP-ERROR] '.$e->getMessage());
    error_log('[VERCEL-APP-ERROR] '.$e->getFile().':'.$e->getLine());
    error_log('[VERCEL-APP-ERROR] '.$e->getTraceAsString());
    
    // Return JSON error (more debuggable than HTML)
    if (!headers_sent()) {
        header('HTTP/1.1 500 Internal Server Error');
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
    exit(1);
}

// Send response properly
if (!headers_sent()) {
    $response->send();
} else {
    echo $response->getContent();
}

$kernel->terminate($request, $response);
