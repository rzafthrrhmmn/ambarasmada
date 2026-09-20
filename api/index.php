<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Throwable;

define('LARAVEL_START', microtime(true));

ini_set('display_errors', '1');
error_reporting(E_ALL);

file_put_contents('/tmp/vercel_marker_start', 'START '.date('c'));

function vercelLog(string $message): void
{
    file_put_contents('/tmp/vercel_debug.log', date('c').' '.$message."\n", FILE_APPEND | LOCK_EX);
    error_log('[VERCEL-DEBUG] '.$message);
}

vercelLog('STEP 1: api/index.php started');

try {
    $storagePath = '/tmp/storage';

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
        if (! is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    vercelLog('STEP 2: storage directories ready');

    $sessionDriver = trim((string) getenv('SESSION_DRIVER')) ?: 'file';

    $cacheStore = trim(
        (string) (getenv('CACHE_STORE') ?: getenv('CACHE_DRIVER'))
    ) ?: 'file';

    $dbConnection = trim((string) getenv('DB_CONNECTION')) ?: 'mysql';

    $queueConnection = trim((string) getenv('QUEUE_CONNECTION')) ?: 'sync';

    vercelLog('STEP 3: environment read');

    vercelLog('SESSION_DRIVER = '.$sessionDriver);
    vercelLog('CACHE_STORE = '.$cacheStore);
    vercelLog('DB_CONNECTION = '.$dbConnection);
    vercelLog('QUEUE_CONNECTION = '.$queueConnection);

    putenv('APP_STORAGE='.$storagePath);
    putenv('VIEW_COMPILED_PATH='.$storagePath.'/framework/views');

    putenv(
        'APP_SERVICES_CACHE='.$storagePath.'/../bootstrap/cache/services.php'
    );

    putenv(
        'APP_PACKAGES_CACHE='.$storagePath.'/../bootstrap/cache/packages.php'
    );

    putenv(
        'APP_CONFIG_CACHE='.$storagePath.'/../bootstrap/cache/config.php'
    );

    putenv(
        'APP_ROUTES_CACHE='.$storagePath.'/../bootstrap/cache/routes.php'
    );

    putenv(
        'APP_EVENTS_CACHE='.$storagePath.'/../bootstrap/cache/events.php'
    );

    putenv('SESSION_DRIVER='.$sessionDriver);
    putenv('CACHE_STORE='.$cacheStore);
    putenv('CACHE_DRIVER='.$cacheStore);
    putenv('DB_CONNECTION='.$dbConnection);
    putenv('QUEUE_CONNECTION='.$queueConnection);

    $_ENV['SESSION_DRIVER'] = $sessionDriver;
    $_SERVER['SESSION_DRIVER'] = $sessionDriver;

    $_ENV['CACHE_STORE'] = $cacheStore;
    $_SERVER['CACHE_STORE'] = $cacheStore;

    $_ENV['CACHE_DRIVER'] = $cacheStore;
    $_SERVER['CACHE_DRIVER'] = $cacheStore;

    $_ENV['DB_CONNECTION'] = $dbConnection;
    $_SERVER['DB_CONNECTION'] = $dbConnection;

    $_ENV['QUEUE_CONNECTION'] = $queueConnection;
    $_SERVER['QUEUE_CONNECTION'] = $queueConnection;

    vercelLog('STEP 4: environment overrides applied');

    vercelLog('STEP 5: before composer autoload');
    require __DIR__.'/../vendor/autoload.php';
    vercelLog('STEP 5b: composer autoload loaded');

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    vercelLog('STEP 6: bootstrap/app.php loaded');

    $app->useStoragePath($storagePath);

    vercelLog('STEP 7: storage path configured');

    $app->booting(function () use (
        $sessionDriver,
        $cacheStore,
        $dbConnection,
        $queueConnection
    ) {
        config([
            'session.driver' => $sessionDriver,
            'cache.default' => $cacheStore,
            'database.default' => $dbConnection,
            'queue.default' => $queueConnection,
        ]);

        error_log('[VERCEL-DEBUG] STEP 8: configuration overridden');
    });

    $request = Request::capture();

    vercelLog(
        'STEP 9: request captured: '.$request->method().' '.$request->path()
    );

    vercelLog('STEP 9b: about to call handleRequest');

    $response = $app->handleRequest($request);

    vercelLog('STEP 10: handleRequest completed');

    vercelLog('STEP 11: about to send response');

    if (! headers_sent()) {
        $response->send();
    }

    vercelLog('STEP 12: response sent');

    exit(0);

} catch (Throwable $e) {

    error_log('[VERCEL-DEBUG] ===============================');
    error_log('[VERCEL-DEBUG] FATAL EXCEPTION');
    error_log('[VERCEL-DEBUG] CLASS: '.get_class($e));
    error_log('[VERCEL-DEBUG] MESSAGE: '.$e->getMessage());
    error_log('[VERCEL-DEBUG] FILE: '.$e->getFile());
    error_log('[VERCEL-DEBUG] LINE: '.$e->getLine());
    error_log('[VERCEL-DEBUG] TRACE: '.$e->getTraceAsString());
    error_log('[VERCEL-DEBUG] ===============================');

    file_put_contents('/tmp/vercel_exception_'.time().'.txt', $e->getTraceAsString());

    exit(1);
}
