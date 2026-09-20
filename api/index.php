<?php
// Minimal test - REMOVE AFTER DEBUGGING

error_log('[VERCEL-MINIMAL] Starting minimal test');

// Step 1: Basic PHP output
echo "MINIMAL_TEST_OK\n";

// Step 2: Check vendor autoload
error_log('[VERCEL-MINIMAL] Step 2: Checking autoload');
if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    error_log('[VERCEL-MINIMAL] ERROR: vendor/autoload.php not found');
    echo "ERROR: autoload not found";
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';
echo "AUTOLOAD_OK\n";

error_log('[VERCEL-MINIMAL] Step 2 OK: autoload loaded');

// Step 3: Set storage path env var BEFORE bootstrap
error_log('[VERCEL-MINIMAL] Step 3: Setting APP_STORAGE');
$storagePath = '/tmp/storage';
mkdir($storagePath.'/logs', 0755, true);
putenv('APP_STORAGE='.$storagePath);
putenv('VIEW_COMPILED_PATH='.$storagePath.'/framework/views');
putenv('APP_CONFIG_CACHE='.$storagePath.'/../bootstrap/cache/config.php');
putenv('APP_SERVICES_CACHE='.$storagePath.'/../bootstrap/cache/services.php');
putenv('APP_PACKAGES_CACHE='.$storagePath.'/../bootstrap/cache/packages.php');
putenv('APP_ROUTES_CACHE='.$storagePath.'/../bootstrap/cache/routes.php');
putenv('APP_EVENTS_CACHE='.$storagePath.'/../bootstrap/cache/events.php');
putenv('SESSION_DRIVER=file');
putenv('CACHE_STORE=file');
putenv('CACHE_DRIVER=file');
putenv('QUEUE_CONNECTION=sync');
putenv('DB_CONNECTION=pgsql');

error_log('[VERCEL-MINIMAL] Step 3 OK');

// Step 4: Bootstrap Laravel
echo "BOOTSTRAP_START\n";
error_log('[VERCEL-MINIMAL] Step 4: Bootstrapping Laravel');

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';
echo "APP_CREATED\n";

error_log('[VERCEL-MINIMAL] Step 4a: Application created');

// Override storage path
$app->useStoragePath($storagePath);
error_log('[VERCEL-MINIMAL] Step 4b: Storage path overridden');

// Override config for logging
$app['config']['logging.default'] = 'stderr';
error_log('[VERCEL-MINIMAL] Step 4c: Logging overridden to stderr');

// Step 5: Kernel
echo "KERNEL_START\n";
error_log('[VERCEL-MINIMAL] Step 5: Kernel resolve');

try {
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    echo "KERNEL_OK\n";
    
    $request = \Illuminate\Http\Request::capture();
    error_log('[VERCEL-MINIMAL] Step 6: Request captured');
    
    $response = $kernel->handle($request);
    error_log('[VERCEL-MINIMAL] Step 7: Handle OK');
    
    if (!headers_sent()) {
        $response->send();
        error_log('[VERCEL-MINIMAL] Step 8: Response sent normally');
    } else {
        echo $response->getContent();
        error_log('[VERCEL-MINIMAL] Step 8a: Headers already sent, body sent');
    }
    
    $kernel->terminate($request, $response);
    error_log('[VERCEL-MINIMAL] Step 9: Done');
    
    echo "DONE_OK\n";
} catch (\Throwable $e) {
    error_log('[VERCEL-MINIMAL-CATCH] '.$e->getMessage());
    error_log('[VERCEL-MINIMAL-CATCH] '.$e->getTraceAsString());
}
