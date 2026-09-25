#!/bin/bash
# This script runs inside the vercel-php runtime after Composer install
# It handles Laravel production optimizations and migrations

set -e

# Create storage directories
mkdir -p /tmp/storage/framework/{sessions,views,cache}
mkdir -p /tmp/storage/logs
mkdir -p /tmp/storage/app/public

# Clear and cache config for production
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Run migrations
php artisan migrate --force

# Clear optimized cache
php artisan optimize:clear

echo "vercel-build.sh completed successfully"
