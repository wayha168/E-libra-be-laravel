#!/usr/bin/env bash
# Idempotent bootstrap for the Cursor Cloud Agent environment.
# Runs after the repository is checked out. Safe to run repeatedly.
set -euo pipefail
cd "$(dirname "$0")/.."

echo "==> composer install"
composer install --no-interaction --prefer-dist

echo "==> ensure .env"
if [ ! -f .env ]; then
  cp .env.example .env
fi

echo "==> app key"
if ! grep -qE '^APP_KEY=base64:' .env; then
  php artisan key:generate --force
fi

echo "==> storage directories + symlink"
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
php artisan storage:link --force

echo "==> ensure sqlite database"
touch database/database.sqlite

echo "==> migrate + seed"
php artisan migrate --force --seed

echo "==> node dependencies"
npm ci --ignore-scripts

echo "==> build front-end assets (public/build is gitignored)"
npm run build

echo "==> clear + refresh caches"
php artisan optimize:clear
php artisan scramble:clear || true

echo "Cloud Agent setup complete."
