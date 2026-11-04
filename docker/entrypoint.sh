#!/bin/sh
set -eu

cd /var/www/html
umask 002

mkdir -p \
    storage/app/public \
    storage/app/private/backups \
    storage/app/private/backup-temp \
    storage/app/private/installation \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

if [ ! -f storage/.docker-initialized ] || [ "$(stat -c '%u' storage)" != "33" ]; then
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R ug+rwX storage bootstrap/cache
    touch storage/.docker-initialized
    chown www-data:www-data storage/.docker-initialized
fi

if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
fi

exec "$@"
