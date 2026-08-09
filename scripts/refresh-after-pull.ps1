# Run after: git pull origin main
# Rebuilds Vite CSS/JS (public/build is gitignored) and refreshes Scramble /docs/api.

$ErrorActionPreference = "Stop"
Set-Location (Split-Path -Parent $PSScriptRoot)

Write-Host "==> composer install"
composer install --no-interaction --prefer-dist

Write-Host "==> clear Laravel + Scramble caches"
php artisan optimize:clear
php artisan scramble:clear

Write-Host "==> migrate"
php artisan migrate --force

Write-Host "==> npm install + build (styles)"
npm install --ignore-scripts
npm run build

Write-Host "Done. Hard-refresh the browser (Ctrl+F5). Docs: /docs/api"
