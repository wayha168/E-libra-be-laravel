#!/usr/bin/env bash
# Fix production image 404 + prepare Reverb. Run on the server as root.
#   bash scripts/fix-production-storage-reverb.sh

set -euo pipefail
APP_DIR="${1:-/var/www/E-libra-be-laravel}"
cd "$APP_DIR"

echo "==> storage:link (fixes /storage/... 404 when files exist)"
php artisan storage:link --force || true
mkdir -p storage/app/public/uploads
chown -R www-data:www-data storage bootstrap/cache public/storage 2>/dev/null || true
chmod -R 775 storage bootstrap/cache

echo "==> check symlink"
ls -la public/storage || true

echo "==> sample upload path (404 if file missing on THIS server)"
ls -la storage/app/public/uploads/profile 2>/dev/null | head -n 20 || echo "No profile uploads yet — re-upload images on production or rsync from local."

echo "==> clear caches"
php artisan optimize:clear

echo ""
echo "If WebSocket still fails (wss://.../app/...):"
echo "  1. Put deploy/reverb.service in /etc/systemd/system/elibra-reverb.service"
echo "  2. systemctl daemon-reload && systemctl enable --now elibra-reverb"
echo "  3. Add nginx location /app from deploy/nginx-elibra.conf.example"
echo "  4. In .env set REVERB_HOST=elibra.skinme.store REVERB_PORT=443 REVERB_SCHEME=https"
echo "     and matching VITE_REVERB_* then: npm run build && php artisan config:clear"
echo "  5. ss -tlnp | grep 8080   # Reverb must listen"
echo "  6. nginx -t && systemctl reload nginx"
