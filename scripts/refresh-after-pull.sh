#!/usr/bin/env bash
# Run after: git pull origin main
# Rebuilds Vite CSS/JS (public/build is gitignored) and refreshes Scramble /docs/api.

set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> composer install"
composer install --no-interaction --prefer-dist

echo "==> clear Laravel + Scramble caches"
php artisan optimize:clear
php artisan scramble:clear

echo "==> migrate"
php artisan migrate --force

echo "==> npm install + build (styles)"
npm install --ignore-scripts
npm run build

echo "Done. Hard-refresh the browser. Docs: /docs/api"
