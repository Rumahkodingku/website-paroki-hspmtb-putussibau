# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3.36
ARG NODE_VERSION=22.22.3
ARG COMPOSER_VERSION=2.8.12

FROM node:${NODE_VERSION}-bookworm-slim AS node

FROM composer:${COMPOSER_VERSION} AS composer

FROM php:${PHP_VERSION}-fpm-bookworm AS builder

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/tmp/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY --from=node /usr/local /usr/local

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

COPY package.json package-lock.json ./
RUN HUSKY=0 npm ci

COPY . .

RUN mkdir -p \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && composer dump-autoload --optimize --no-dev \
    && DB_CONNECTION=sqlite DB_DATABASE=:memory: npm run build \
    && test -f public/build/manifest.json

FROM php:${PHP_VERSION}-fpm-bookworm AS phpext

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libwebp-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip exif pcntl \
    && rm -rf /var/lib/apt/lists/*

FROM php:${PHP_VERSION}-fpm-bookworm AS runtime

ENV TZ=UTC \
    COMPOSER_ALLOW_SUPERUSER=1 \
    DEBIAN_FRONTEND=noninteractive

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        nginx \
        supervisor \
        tzdata \
        libpng16-16 \
        libjpeg62-turbo \
        libfreetype6 \
        libwebp7 \
        libzip4 \
        libonig5 \
        gnupg \
    && install -d /usr/share/keyrings \
    && curl -fsSL https://repo.mysql.com/RPM-GPG-KEY-mysql-2025 \
        | gpg --dearmor -o /usr/share/keyrings/mysql.gpg \
    && echo 'deb [signed-by=/usr/share/keyrings/mysql.gpg] http://repo.mysql.com/apt/debian/ bookworm mysql-8.4-lts' \
        > /etc/apt/sources.list.d/mysql.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        mysql-community-client=8.4.13-1debian12 \
    && apt-get purge -y --auto-remove gnupg \
    && rm -rf /var/lib/apt/lists/* \
    && mysql --version \
    && mysqldump --version

COPY --from=phpext /usr/local/lib/php/extensions /usr/local/lib/php/extensions
COPY --from=phpext /usr/local/etc/php/conf.d /usr/local/etc/php/conf.d

COPY docker/php/production.ini /usr/local/etc/php/conf.d/zz-production.ini
COPY docker/php-fpm/www.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/app.conf
COPY docker/entrypoint.sh /usr/local/bin/docker-entrypoint.sh
COPY docker/healthcheck.sh /usr/local/bin/healthcheck.sh

RUN rm -f /etc/nginx/sites-enabled/default \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && chmod +x /usr/local/bin/docker-entrypoint.sh /usr/local/bin/healthcheck.sh \
    && mkdir -p \
        storage/app/public \
        storage/app/private/backups \
        storage/app/private/backup-temp \
        storage/app/private/installation \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

WORKDIR /var/www/html

COPY . .
COPY --from=builder /var/www/html/vendor ./vendor
COPY --from=builder /var/www/html/public/build ./public/build
COPY --from=builder /var/www/html/bootstrap/cache ./bootstrap/cache

RUN chown -R www-data:www-data storage bootstrap/cache

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=3 \
    CMD ["/usr/local/bin/healthcheck.sh"]

CMD ["/usr/local/bin/docker-entrypoint.sh", "/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/app.conf"]
