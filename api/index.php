<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Throwable;

define('LARAVEL_START', microtime(true));

function vercelLog(string $message): void
{
    error_log('[VERCEL-DEBUG] '.$message);
}

vercelLog('STEP 1: api/index.php started');

try {
    /*
    |--------------------------------------------------------------------------
    | Temporary storage for Vercel
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Environment
    |--------------------------------------------------------------------------
    */

    $sessionDriver = trim((string) getenv('SESSION_DRIVER')) ?: 'file';

    $cacheStore = trim(
        (string) (getenv('CACHE_STORE') ?: getenv('CACHE_DRIVER'))
    ) ?: 'file';

    $dbConnection = trim((string) getenv('DB_CONNECTION')) ?: 'mysql';

    $queueConnection = trim((string) getenv('QUEUE_CONNECTION')) ?: 'sync';

    vercelLog('STEP 3: environment read');

    /*
    |--------------------------------------------------------------------------
    | IMPORTANT:
    | Only log driver names, never secrets.
    |--------------------------------------------------------------------------
    */

    vercelLog('SESSION_DRIVER = '.($sessionDriver ?: '[EMPTY]'));
    vercelLog('CACHE_STORE = '.($cacheStore ?: '[EMPTY]'));
    vercelLog('DB_CONNECTION = '.($dbConnection ?: '[EMPTY]'));
    vercelLog('QUEUE_CONNECTION = '.($queueConnection ?: '[EMPTY]'));

    /*
    |--------------------------------------------------------------------------
    | Force safe environment values
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Composer
    |--------------------------------------------------------------------------
    */

    require __DIR__.'/../vendor/autoload.php';

    vercelLog('STEP 5: composer autoload loaded');

    /*
    |--------------------------------------------------------------------------
    | Laravel Application
    |--------------------------------------------------------------------------
    */

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    vercelLog('STEP 6: bootstrap/app.php loaded');

    /*
    |--------------------------------------------------------------------------
    | Force application storage path
    |--------------------------------------------------------------------------
    */

    $app->useStoragePath($storagePath);

    vercelLog('STEP 7: storage path configured');

    /*
    |--------------------------------------------------------------------------
    | Force configuration
    |--------------------------------------------------------------------------
    */

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

    /*
    |--------------------------------------------------------------------------
    | Request
    |--------------------------------------------------------------------------
    */

    $request = Request::capture();

    vercelLog('STEP 9: request captured: '.$request->method().' '.$request->path());

    /*
    |--------------------------------------------------------------------------
    | Laravel request handling
    |--------------------------------------------------------------------------
    */

    $response = $app->handleRequest($request);

    vercelLog('STEP 10: Laravel returned response');

    return $response;

} catch (Throwable $e) {

    /*
    |--------------------------------------------------------------------------
    | Diagnostic logging
    |--------------------------------------------------------------------------
    */

    error_log('[VERCEL-DEBUG] ===============================');
    error_log('[VERCEL-DEBUG] FATAL EXCEPTION');
    error_log('[VERCEL-DEBUG] CLASS: '.get_class($e));
    error_log('[VERCEL-DEBUG] MESSAGE: '.$e->getMessage());
    error_log('[VERCEL-DEBUG] FILE: '.$e->getFile());
    error_log('[VERCEL-DEBUG] LINE: '.$e->getLine());
    error_log('[VERCEL-DEBUG] TRACE:');
    error_log($e->getTraceAsString());
    error_log('[VERCEL-DEBUG] ===============================');

    http_response_code(500);

    echo 'Vercel Laravel bootstrap error: '.$e->getMessage();

    exit;
}
