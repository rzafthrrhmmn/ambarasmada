<?php

// Vercel-specific: Start output buffering untuk mencegah "headers already sent"
ob_start();

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Throwable;

define('LARAVEL_START', microtime(true));

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

    require __DIR__.'/../vendor/autoload.php';

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

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
        // Clear output buffer lalu kirim content
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo $response->getContent();
    }

    $kernel->terminate($request, $response);

    // Clean up output buffer
    if (ob_get_level() > 0) {
        ob_end_clean();
    }

} catch (Throwable $e) {
    error_log('[VERCEL-CATCH] '.$e->getMessage());
    error_log('[VERCEL-CATCH] '.$e->getTraceAsString());
}
