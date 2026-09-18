# Base image for the E-libra Laravel backend Cloud Agent environment.
# Provides PHP 8.3 (with the extensions Laravel + fpdf/gd/sqlite/mysql need),
# Composer, and Node.js 22 for building Vite assets.
FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# System packages + PHP 8.3 CLI and extensions.
# Extensions mirror CI (dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite,
# pdo_sqlite) plus gd (setasign/fpdf) and mysql (docker-compose parity).
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip \
        php8.3-cli php8.3-common php8.3-mbstring php8.3-xml php8.3-curl \
        php8.3-sqlite3 php8.3-mysql php8.3-gd php8.3-zip php8.3-bcmath php8.3-intl \
    && rm -rf /var/lib/apt/lists/*

# Node.js 22 (required to build Vite assets; public/build is gitignored).
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

# Composer.
RUN curl -sS https://getcomposer.org/installer \
    | php -- --install-dir=/usr/local/bin --filename=composer

# Allow large book PDF uploads (matches BOOK_PDF_MAX_KB default of 512 MB).
RUN printf "upload_max_filesize=512M\npost_max_size=520M\nmemory_limit=512M\nmax_execution_time=300\n" \
    > /etc/php/8.3/cli/conf.d/99-elibra-uploads.ini

# The base image ships an unprivileged `ubuntu` user (UID 1000); run as it.
USER ubuntu
WORKDIR /home/ubuntu
