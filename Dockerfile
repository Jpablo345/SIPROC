# syntax=docker/dockerfile:1

# Imagen de SIPROC: PHP 8.3 servido por FrankenPHP (Caddy + PHP en un solo proceso).
# La misma imagen corre la web, el worker de colas y el scheduler (ver compose.yaml).

# ── 1. Dependencias de PHP ────────────────────────────────────────────────
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
# Las extensiones se instalan en la etapa final; aquí solo se descargan los paquetes.
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# ── 2. CSS y JS ──────────────────────────────────────────────────────────
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
# Tailwind lee las vistas de paginación de Laravel y Livewire para no omitir sus clases.
COPY --from=vendor /app/vendor/laravel/framework/src/Illuminate/Pagination/resources/views ./vendor/laravel/framework/src/Illuminate/Pagination/resources/views
COPY --from=vendor /app/vendor/livewire/livewire/src/Features/SupportPagination/views ./vendor/livewire/livewire/src/Features/SupportPagination/views
RUN npm run build

# ── 3. Imagen final ──────────────────────────────────────────────────────
FROM dunglas/frankenphp:1-php8.3 AS app

RUN install-php-extensions pdo_pgsql pgsql gd zip intl opcache redis bcmath pcntl

COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-siproc.ini"
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY --from=vendor /app/vendor ./vendor
COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/siproc-entrypoint
RUN chmod +x /usr/local/bin/siproc-entrypoint

EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=20s --retries=3 \
    CMD curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["siproc-entrypoint"]
CMD ["frankenphp", "php-server", "--listen", ":80", "--root", "/app/public"]
