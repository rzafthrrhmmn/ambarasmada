#!/bin/bash
set -e

# Find @libphp package path
LIBPHP_DIR=$(node -e "try { const p = require.resolve('@libphp/almalinux-9-v85/package.json'); console.log(require('path').dirname(p)); } catch(e) { console.error(e); process.exit(1); }")

export LD_LIBRARY_PATH="$LIBPHP_DIR/native/lib:/usr/lib64:/lib64:${LD_LIBRARY_PATH:-}"
export PHP_INI_EXTENSION_DIR="$LIBPHP_DIR/native/php/modules"
export PHP_INI_SCAN_DIR=":$LIBPHP_DIR/conf"

PHP_BIN="$LIBPHP_DIR/native/php/php"
INI_FILE="$LIBPHP_DIR/native/php/php.ini"

# Check if composer is bundled with @libphp, otherwise download it
if [ -f "$LIBPHP_DIR/native/php/composer" ]; then
    COMPOSER_CMD="$LIBPHP_DIR/native/php/composer"
else
    echo "Composer not bundled, downloading..."
    $PHP_BIN -c $INI_FILE -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_BIN -c $INI_FILE composer-setup.php --install-dir="$LIBPHP_DIR/native/php"
    rm -f composer-setup.php
    COMPOSER_CMD="$LIBPHP_DIR/native/php/composer"
fi

echo "Running composer install..."
$PHP_BIN -c $INI_FILE "$COMPOSER_CMD" install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader

# Run package discover for Laravel using explicit paths
echo "Running package:discover..."
APP_PACKAGES_CACHE=/tmp/bootstrap/cache/packages.php \
APP_SERVICES_CACHE=/tmp/bootstrap/cache/services.php \
$PHP_BIN -c $INI_FILE -d extension_dir="$PHP_INI_EXTENSION_DIR" ./artisan package:discover --ansi || true

echo "Build complete."
