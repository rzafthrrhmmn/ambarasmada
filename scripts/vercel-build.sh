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
    $PHP_BIN -c $INI_FILE -d extension_dir="$PHP_INI_EXTENSION_DIR" -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    $PHP_BIN -c $INI_FILE -d extension_dir="$PHP_INI_EXTENSION_DIR" composer-setup.php --install-dir="$LIBPHP_DIR/native/php"
    rm -f composer-setup.php
    COMPOSER_CMD="$LIBPHP_DIR/native/php/composer"
fi

echo "Running composer install..."
$PHP_BIN -c $INI_FILE -d extension_dir="$PHP_INI_EXTENSION_DIR" "$COMPOSER_CMD" install \
  --no-dev \
  --no-interaction \
  --prefer-dist \
  --optimize-autoloader

# Run package discover for Laravel (writes to bootstrap/cache/ so they're
# included in app-files.tar.gz; without dev packages pail/tinker/collision)
echo "Running package:discover..."
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php
$PHP_BIN -c $INI_FILE -d extension_dir="$PHP_INI_EXTENSION_DIR" ./artisan package:discover --ansi || true

# Create a node_modules package to bundle vendor/ + app files via @vercel/nft
# vendor/ has ~16000 files (exceeds Vercel's 15000 file limit), so we tar it.
# Application PHP files are also tar'd since @vercel/nft only traces JS imports.
echo "Packaging for Lambda..."
mkdir -p node_modules/laravel-vendor
echo '{"name":"laravel-vendor","main":"index.js","files":["index.js","vendor.tar.gz","app-files.tar.gz"]}' > node_modules/laravel-vendor/package.json
echo 'try { require.resolve("./vendor.tar.gz"); } catch (e) {} try { require.resolve("./app-files.tar.gz"); } catch (e) {} module.exports = {};' > node_modules/laravel-vendor/index.js

# Create vendor tarball (exclude tests, docs, .git, etc. to reduce size)
tar -czf node_modules/laravel-vendor/vendor.tar.gz \
    --exclude='*/tests/*' --exclude='*/test/*' \
    --exclude='*/phpunit.xml*' \
    --exclude='*/.git' --exclude='*/docs/*' --exclude='*/examples/*' \
    --exclude='*/README*' --exclude='*/CHANGELOG*' --exclude='*/UPGRADE*' \
    --exclude='*/LICENSE*' --exclude='*/composer.lock' \
    --exclude='*/.github/*' --exclude='*/.gitattributes' --exclude='*/.gitignore' \
    -C vendor .

# Create app files tarball (exclude vendor, node_modules, .git, tests, debug files, pmtiles)
tar -czf node_modules/laravel-vendor/app-files.tar.gz \
    --exclude=vendor --exclude=node_modules --exclude=.git --exclude=.kilo \
    --exclude=tests --exclude='_probe*' --exclude=test_hash* --exclude=check_login.php \
    --exclude='*.pmtiles' --exclude='.github' .

echo "Build complete."
