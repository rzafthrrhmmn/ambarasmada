#!/bin/bash
# This script runs inside the vercel-php runtime after Composer install

set -e

# Ensure bootstrap/cache directory exists (required by Laravel PackageManifest)
mkdir -p bootstrap/cache
echo "Laravel runtime build completed"
