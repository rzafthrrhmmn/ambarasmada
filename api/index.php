<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Contracts\Http\Kernel;

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
    $_ENV['APP_STORAGE'] = $storagePath;
    $_SERVER['APP_STORAGE'] = $storagePath;
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

    $cacheFiles = [
        $storagePath.'/../bootstrap/cache/config.php',
        $storagePath.'/../bootstrap/cache/services.php',
        $storagePath.'/../bootstrap/cache/packages.php',
        $storagePath.'/../bootstrap/cache/routes.php',
        $storagePath.'/../bootstrap/cache/events.php',
        __DIR__.'/../bootstrap/cache/config.php',
        __DIR__.'/../bootstrap/cache/services.php',
        __DIR__.'/../bootstrap/cache/packages.php',
        __DIR__.'/../bootstrap/cache/routes.php',
        __DIR__.'/../bootstrap/cache/events.php',
        __DIR__.'/../bootstrap/cache/app.php',
    ];

    foreach ($cacheFiles as $cacheFile) {
        if (is_file($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    $_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
    $_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1';

    $maintenance = __DIR__.'/../storage/framework/maintenance.php';
    if (file_exists($maintenance)) {
        require $maintenance;
    }

    require __DIR__.'/../vendor/autoload.php';

    /** @var Application $app */
    $app = require_once __DIR__.'/../bootstrap/app.php';

    $app->useStoragePath($storagePath);

    $app->booting(function () use ($sessionDriver, $cacheStore, $dbConnection, $queueConnection) {
        error_log('[VERCEL-BOOT] Setting config values');
        config([
            'session.driver' => $sessionDriver,
            'cache.default' => $cacheStore,
            'database.default' => $dbConnection,
            'queue.default' => $queueConnection,
        ]);
    });

    $app->booted(function () use ($app) {
        error_log('[VERCEL-BOOT] Setting maintenance driver config');
        $app['config']->set('app.maintenance.driver', 'file');
        $app['config']->set('app.maintenance.store', 'file');
        error_log('[VERCEL-BOOT] Maintenance driver: '.$app['config']->get('app.maintenance.driver'));
    });

    $app->instance(\Illuminate\Contracts\Debug\ExceptionHandler::class, new class($app) extends \Illuminate\Foundation\Exceptions\Handler {
        public function render($request, \Throwable $e)
        {
            http_response_code(500);
            header('Content-Type: text/plain; charset=UTF-8');
            echo '[VERCEL-ERROR] '.$e->getMessage()."\n".$e->getTraceAsString();
            exit;
        }
    });

    $app->boot();

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
    error_log('[VERCEL-ERROR] '.$e->getMessage());
    error_log('[VERCEL-ERROR] '.$e->getTraceAsString());

    if (! headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=UTF-8');
        echo '[VERCEL-ERROR] '.$e->getMessage()."\n".$e->getTraceAsString();
    } else {
        echo '[VERCEL-ERROR] '.$e->getMessage();
    }
}