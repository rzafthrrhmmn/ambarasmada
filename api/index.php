<?php

echo "Phase 1: PHP OK\n";

// Test autoload
echo "Phase 2: Before autoload\n";

if (!file_exists(__DIR__.'/../vendor/autoload.php')) {
    echo "Phase 2 ERROR: vendor/autoload.php not found\n";
    exit(1);
}

require __DIR__.'/../vendor/autoload.php';
echo "Phase 2: Autoload OK\n";

// Test bootstrap
echo "Phase 3: Before bootstrap/app.php\n";

if (!file_exists(__DIR__.'/../bootstrap/app.php')) {
    echo "Phase 3 ERROR: bootstrap/app.php not found\n";
    exit(1);
}

/** @var \Illuminate\Foundation\Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

echo "Phase 4: Application created\n";

// Test make Kernel
echo "Phase 5: Before Kernel resolve\n";

try {
    $kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);
    echo "Phase 5: Kernel resolved\n";
} catch (\Throwable $e) {
    echo "Phase 5 ERROR: ".$e->getMessage()."\n";
    error_log('[VERCEL-PHASE5] '.$e->getMessage());
    exit(1);
}

echo "Phase 6: Before handle\n";

try {
    $request = \Illuminate\Http\Request::capture();
    $response = $kernel->handle($request);
    echo "Phase 6: Handle OK\n";
    
    if (!headers_sent()) {
        $response->send();
    } else {
        echo $response->getContent();
    }
    
    $kernel->terminate($request, $response);
    echo "Phase 7: Done\n";
} catch (\Throwable $e) {
    echo "Phase 6 ERROR: ".$e->getMessage()."\n";
    error_log('[VERCEL-PHASE6] '.$e->getMessage());
    error_log('[VERCEL-PHASE6-TRACE] '.$e->getTraceAsString());
    exit(1);
}
