#!/bin/sh
# Prepara Laravel antes de arrancar el proceso del contenedor (web, worker o scheduler).
set -e

cd /app

if [ -z "$APP_KEY" ]; then
    echo "Falta APP_KEY. Genera una con: php artisan key:generate --show" >&2
    exit 1
fi

# Cachés de producción: se arman aquí porque dependen de las variables de entorno del contenedor.
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ ! -L public/storage ]; then
    php artisan storage:link || true
fi

exec "$@"
