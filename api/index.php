<?php
// Emergency test - no Laravel bootstrap
error_reporting(E_ALL);
ini_set('display_errors', '1');

echo "PHP_OK " . PHP_VERSION . "\n";
echo "AUTOLOAD: " . (file_exists(__DIR__.'/../vendor/autoload.php') ? 'OK' : 'MISSING') . "\n";
echo "BOOTSTRAP: " . (file_exists(__DIR__.'/../bootstrap/app.php') ? 'OK' : 'MISSING') . "\n";
echo "SYMFONY_VERSION: " . \Symfony\Component\HttpKernel\Kernel::VERSION . "\n";

// Test vendor directory
if (is_dir(__DIR__.'/../vendor/symfony/http-foundation')) {
    echo "SYMFONY_HTTP_FOUNDATION: " . scandir(__DIR__.'/../vendor/symfony/http-foundation')['0'] . "\n";
    echo "REQUEST_PHP_EXISTS: " . (file_exists(__DIR__.'/../vendor/symfony/http-foundation/Request.php') ? 'YES' : 'NO') . "\n";
} else {
    echo "SYMFONY_HTTP_FOUNDATION_DIR: MISSING\n";
}

// Check for conflicting Symfony versions
$dirs = glob(__DIR__.'/../vendor/symfony/*', GLOB_ONLYDIR);
foreach ($dirs as $dir) {
    if (is_file($dir.'/composer.json')) {
        $json = json_decode(file_get_contents($dir.'/composer.json'), true);
        echo basename($dir) . ": " . ($json['version'] ?? 'unknown') . " ";
    }
}
echo "\n";
