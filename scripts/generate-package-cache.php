<?php

// This script generates bootstrap/cache/packages.php in the project directory
// so it gets included in the Vercel deployment.
// It overrides APP_PACKAGES_CACHE to avoid writing to /tmp/ (ephemeral on Vercel build).

$_ENV['APP_PACKAGES_CACHE'] = 'bootstrap/cache/packages.php';
$_SERVER['APP_PACKAGES_CACHE'] = 'bootstrap/cache/packages.php';
putenv('APP_PACKAGES_CACHE=bootstrap/cache/packages.php');

try {
    require __DIR__.'/../vendor/autoload.php';

    use Illuminate\Filesystem\Filesystem;
    use Illuminate\Foundation\PackageManifest;

    $basePath = realpath(__DIR__.'/..');
    $manifest = new PackageManifest(
        new Filesystem(),
        $basePath,
        $basePath.'/bootstrap/cache/packages.php'
    );

    $manifest->build();

    echo "Package manifest written to: {$basePath}/bootstrap/cache/packages.php\n";
} catch (Throwable $e) {
    error_log('[BUILD] package:discover failed: '.$e->getMessage());
    // Don't fail the build — a pre-committed packages.php may still exist
    exit(0);
}
