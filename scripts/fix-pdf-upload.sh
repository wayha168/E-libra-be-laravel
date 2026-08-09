#!/usr/bin/env bash
# Fix PDF "failed to upload" on Ubuntu + nginx + php8.3-fpm
set -euo pipefail

echo "==> PHP-FPM upload limits"
sudo tee /etc/php/8.3/fpm/conf.d/99-elibra-uploads.ini >/dev/null <<'EOF'
upload_max_filesize = 512M
post_max_size = 520M
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
EOF

sudo systemctl restart php8.3-fpm
echo "FPM conf.d:"
grep -rE 'upload_max_filesize|post_max_size' /etc/php/8.3/fpm/conf.d/ || true

echo "==> nginx client_max_body_size"
if ! grep -Rqs 'client_max_body_size' /etc/nginx/sites-enabled/ /etc/nginx/nginx.conf 2>/dev/null; then
  echo "WARNING: client_max_body_size not found. Add inside SSL server block:"
  echo "  client_max_body_size 512M;"
else
  grep -Rn 'client_max_body_size' /etc/nginx/sites-enabled/ /etc/nginx/nginx.conf 2>/dev/null || true
fi
sudo nginx -t
sudo systemctl reload nginx

echo "==> Laravel storage (PDFs go to storage/app/private/books)"
APP_DIR="${1:-/var/www/E-libra-be-laravel}"
mkdir -p "$APP_DIR/storage/app/private/books" "$APP_DIR/storage/app/public"
chown -R www-data:www-data "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"
chmod -R 775 "$APP_DIR/storage" "$APP_DIR/bootstrap/cache"

# Show what FPM workers will actually use
echo "==> Effective FPM pool check (via php-fpm -i if available)"
php-fpm8.3 -i 2>/dev/null | grep -E '^upload_max_filesize|^post_max_size' || \
  echo "Run: echo '<?php phpinfo();' > $APP_DIR/public/_upload_check.php && curl -s https://elibra.skinme.store/_upload_check.php | grep -E 'upload_max_filesize|post_max_size' && rm $APP_DIR/public/_upload_check.php"

echo "Done. Retry PDF upload. If it still fails, the new error text will show the real limits."
