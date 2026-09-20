<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;
use Throwable;

define('LARAVEL_START', microtime(true));

// Tangkap fatal error sebelum Laravel bootstrap
register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        file_put_contents('/tmp/vercel_fatal_error.txt', date('c').' '.json_encode($error)."\n", FILE_APPEND | LOCK_EX);
        error_log('[VERCEL-FATAL] '.$error['message']);
    }
});

file_put_contents('/tmp/vercel_debug_marker.txt', 'START '.date('c'));

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

    $sessionDriver = trim((string) getenv('SESSION_DRIVER')) ?: 'file';
    $cacheStore = trim((string) (getenv('CACHE_STORE') ?: getenv('CACHE_DRIVER'))) ?: 'file';
    $dbConnection = trim((string) getenv('DB_CONNECTION')) ?: 'mysql';
    $queueConnection = trim((string) getenv('QUEUE_CONNECTION')) ?: 'sync';

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

    // Autoload vendor
    require __DIR__.'/../vendor/autoload.php';

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    // Set storage path AFTER application creation
    $app->useStoragePath($storagePath);

    $app->booting(function () use ($sessionDriver, $cacheStore, $dbConnection, $queueConnection) {
        config([
            'session.driver' => $sessionDriver,
            'cache.default' => $cacheStore,
            'database.default' => $dbConnection,
            'queue.default' => $queueConnection,
            'logging.default' => 'stderr',
        ]);
    });

    $request = Request::capture();

    /** @var Kernel $kernel */
    $kernel = $app->make(Kernel::class);

    $response = $kernel->handle($request);

    if (! headers_sent()) {
        $response->send();
    } else {
        echo $response->getContent();
    }

    $kernel->terminate($request, $response);

} catch (Throwable $e) {
    error_log('[VERCEL-CATCH] '.$e->getMessage());
    error_log('[VERCEL-CATCH] '.$e->getTraceAsString());
    file_put_contents('/tmp/vercel_catch_error.txt', date('c').' '.json_encode([
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ])."\n", FILE_APPEND | LOCK_EX);
    http_response_code(500);
    echo $e->getMessage();
}
