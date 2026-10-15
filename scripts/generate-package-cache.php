<?php

use Illuminate\Foundation\PackageManifest;

// This script generates bootstrap/cache/packages.php in the project directory
// so it gets included in the Vercel deployment.
// It overrides APP_PACKAGES_CACHE because vercel.json sets it to
// /tmp/bootstrap/cache/packages.php which is not part of the deployment.

$_ENV['APP_PACKAGES_CACHE'] = 'bootstrap/cache/packages.php';
$_SERVER['APP_PACKAGES_CACHE'] = 'bootstrap/cache/packages.php';
putenv('APP_PACKAGES_CACHE=bootstrap/cache/packages.php');

require __DIR__.'/../vendor/autoload.php';

$app = require_once __DIR__.'/../bootstrap/app.php';

$manifest = $app->make(PackageManifest::class);
$manifest->build();

$manifestPath = $app->getCachedPackagesPath();
echo "Package manifest written to: {$manifestPath}\n";
