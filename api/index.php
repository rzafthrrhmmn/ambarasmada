<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Throwable;

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
putenv('SESSION_DRIVER=file');
putenv('CACHE_DRIVER=file');
putenv('CACHE_STORE=file');
putenv('DB_CONNECTION=pgsql');
putenv('QUEUE_CONNECTION=sync');
$_ENV = array_merge($_ENV, [
    'SESSION_DRIVER' => 'file',
    'CACHE_DRIVER' => 'array',
    'CACHE_STORE' => 'array',
    'DB_CONNECTION' => 'pgsql',
    'QUEUE_CONNECTION' => 'sync',
]);
$_SERVER = array_merge($_SERVER, $_ENV);

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$app->useStoragePath($storagePath);
$app['config']->set('events.cache', false);
$app->boot();

$request = Request::capture();
/** @var Kernel $kernel */
$kernel = $app->make(Kernel::class);

try {
    $response = $kernel->handle($request);
} catch (Throwable $e) {
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
