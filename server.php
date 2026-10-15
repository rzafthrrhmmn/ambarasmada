<?php

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

$storagePath = getenv('APP_STORAGE') ?: '/tmp/storage';

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

$appUrl = getenv('APP_URL') ?: 'http://localhost';
putenv('APP_URL='.$appUrl);
$_ENV['APP_URL'] = $appUrl;
$_SERVER['APP_URL'] = $appUrl;

$sessionDriver = trim((string) getenv('SESSION_DRIVER')) ?: 'file';
$cacheStore = trim((string) (getenv('CACHE_STORE') ?: getenv('CACHE_DRIVER'))) ?: 'file';
$dbConnection = trim((string) getenv('DB_CONNECTION')) ?: 'pgsql';
$queueConnection = trim((string) getenv('QUEUE_CONNECTION')) ?: 'sync';
$dbPreparedStatements = trim((string) getenv('DB_PREPARED_STATEMENTS'));

putenv('SESSION_DRIVER='.$sessionDriver);
putenv('CACHE_STORE='.$cacheStore);
putenv('CACHE_DRIVER='.$cacheStore);
putenv('DB_CONNECTION='.$dbConnection);
putenv('QUEUE_CONNECTION='.$queueConnection);
if ($dbPreparedStatements !== '') {
    putenv('DB_PREPARED_STATEMENTS='.$dbPreparedStatements);
}

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
if ($dbPreparedStatements !== '') {
    $_ENV['DB_PREPARED_STATEMENTS'] = $dbPreparedStatements;
    $_SERVER['DB_PREPARED_STATEMENTS'] = $dbPreparedStatements;
}

$cacheFiles = [
    $storagePath.'/../bootstrap/cache/config.php',
    $storagePath.'/../bootstrap/cache/routes.php',
    $storagePath.'/../bootstrap/cache/events.php',
    __DIR__.'/bootstrap/cache/config.php',
    __DIR__.'/bootstrap/cache/routes.php',
    __DIR__.'/bootstrap/cache/events.php',
    __DIR__.'/bootstrap/cache/app.php',
];

foreach ($cacheFiles as $cacheFile) {
    if (is_file($cacheFile)) {
        @unlink($cacheFile);
    }
}

$buildTimeCacheFiles = [
    __DIR__.'/bootstrap/cache/packages.php',
    __DIR__.'/bootstrap/cache/services.php',
];

foreach ($buildTimeCacheFiles as $cacheFile) {
    $tmpFile = $storagePath.'/../bootstrap/cache/'.basename($cacheFile);
    if (is_file($cacheFile)) {
        @copy($cacheFile, $tmpFile);
    }
}

$_SERVER['REMOTE_ADDR'] ??= '127.0.0.1';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?: '127.0.0.1';

$vendorPath = getenv('VENDOR_PATH') ?: __DIR__.'/vendor';

$maintenance = __DIR__.'/storage/framework/maintenance.php';
if (file_exists($maintenance)) {
    require $maintenance;
}

require $vendorPath.'/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/bootstrap/app.php';

$app->useStoragePath($storagePath);

$app->booting(function () use ($sessionDriver, $cacheStore, $dbConnection, $queueConnection) {
    config([
        'session.driver' => $sessionDriver,
        'cache.default' => $cacheStore,
        'database.default' => $dbConnection,
        'queue.default' => $queueConnection,
    ]);

    config([
        'database.connections.pgsql.options' => array_merge(
            config('database.connections.pgsql.options', []),
            [PDO::ATTR_EMULATE_PREPARES => true]
        ),
    ]);
});

$app->booted(function () use ($app) {
    $app['config']->set('app.maintenance.driver', 'file');
    $app['config']->set('app.maintenance.store', 'file');
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
