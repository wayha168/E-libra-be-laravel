# Simple PHP runtime image for running Laravel
FROM php:8.3-fpm

# Install PDO MySQL + common extensions (gd required by setasign/fpdf)
# Node.js is required to build Vite assets (public/build is gitignored)
RUN apt-get update && apt-get install -y \
    git unzip curl ca-certificates \
    libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libxml2-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql gd \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Allow large book PDF uploads (matches BOOK_PDF_MAX_KB default 512 MB)
RUN printf "upload_max_filesize=512M\npost_max_size=520M\nmemory_limit=512M\nmax_execution_time=300\n" \
    > /usr/local/etc/php/conf.d/elibra-uploads.ini

WORKDIR /var/www/html

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Copy the app (artisan must exist for composer post-autoload-dump)
COPY . .

# Install PHP dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Build frontend assets (CSS/JS). Required because /public/build is not in git.
RUN npm install --ignore-scripts \
    && npm run build \
    && rm -rf node_modules

# Link public/storage → storage/app/public (needed for file/upload UI URLs)
RUN mkdir -p storage/app/public storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && php artisan storage:link --force \
    && php artisan scramble:clear || true \
    && chmod -R 775 storage bootstrap/cache

# Expose port for built-in server (used by docker.yaml)
EXPOSE 8000
