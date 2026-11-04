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

# Older volumes can contain root-owned cache files from queue workers that
# used to run as root. Normalize the small framework trees on every start.
chown -R www-data:www-data storage/framework storage/logs
chmod -R ug+rwX storage/framework storage/logs

if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    ln -s ../storage/app/public public/storage
fi

exec "$@"
