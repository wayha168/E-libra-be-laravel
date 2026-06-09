# Simple PHP runtime image for running Laravel
FROM php:8.3-fpm

# Install PDO MySQL + common extensions
RUN apt-get update && apt-get install -y \
    git unzip libpng-dev libonig-dev libxml2-dev \
    && docker-php-ext-install pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copy composer files first (better layer caching)
COPY composer.json composer.lock ./

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install dependencies
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

# Copy the rest of the app
COPY . .

# Expose port for built-in server (used by docker.yaml)
EXPOSE 8000

