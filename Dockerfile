# =============================================================================
# Ticket Center — API  (PHP 8.4 · Laravel 12 · Nginx · Reverb · Supervisord)
# =============================================================================
FROM php:8.4-fpm-alpine

# ── System packages ───────────────────────────────────────────────────────────
RUN apk add --no-cache \
        bash curl nginx supervisor \
        libpng-dev libxml2-dev libzip-dev \
        icu-dev oniguruma-dev \
        linux-headers \
        zip unzip mysql-client

# ── PHP extensions ───────────────────────────────────────────────────────────
RUN docker-php-ext-install \
        pdo_mysql mbstring exif pcntl bcmath gd zip sockets intl opcache

# ── Opcache ──────────────────────────────────────────────────────────────────
RUN { echo "opcache.enable=1"; \
      echo "opcache.memory_consumption=128"; \
      echo "opcache.max_accelerated_files=10000"; \
      echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ── Composer ─────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ── Dependencies ─────────────────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

# ── Application code ──────────────────────────────────────────────────────────
COPY . .
RUN composer dump-autoload --optimize --no-dev

# ── Permissions ──────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www \
 && chmod -R 775 storage bootstrap/cache \
 && mkdir -p storage/logs \
              storage/framework/cache \
              storage/framework/sessions \
              storage/framework/views

# ── Nginx (Removed Manual CORS Headers) ──────────────────────────────────────
RUN printf '%s\n' \
    'user nginx;' \
    'worker_processes auto;' \
    'error_log /dev/stderr warn;' \
    'pid /var/run/nginx.pid;' \
    'events { worker_connections 1024; }' \
    'http {' \
    '    include /etc/nginx/mime.types;' \
    '    default_type application/octet-stream;' \
    '    sendfile on;' \
    '    keepalive_timeout 65;' \
    '    server {' \
    '        listen 8000;' \
    '        server_name _;' \
    '        root /var/www/public;' \
    '        index index.php;' \
    '        location = /favicon.ico { log_not_found off; access_log off; }' \
    '        location / { try_files $uri $uri/ /index.php?$query_string; }' \
    '        location ~ \.php$ {' \
    '            fastcgi_pass 127.0.0.1:9000;' \
    '            fastcgi_index index.php;' \
    '            fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;' \
    '            include fastcgi_params;' \
    '            fastcgi_read_timeout 300;' \
    '        }' \
    '        location ~ /\.ht { deny all; }' \
    '    }' \
    '}' > /etc/nginx/nginx.conf

# ── Supervisord ───────────────────────────────────────────────────────────────
RUN mkdir -p /etc/supervisor/conf.d && printf '%s\n' \
    '[supervisord]' \
    'nodaemon=true' \
    'logfile=/dev/null' \
    'logfile_maxbytes=0' \
    'pidfile=/var/run/supervisord.pid' \
    '' \
    '[program:php-fpm]' \
    'command=php-fpm -F' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stderr_logfile=/dev/stderr' \
    '' \
    '[program:nginx]' \
    'command=nginx -g "daemon off;"' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stderr_logfile=/dev/stderr' \
    '' \
    '[program:reverb]' \
    'command=php /var/www/artisan reverb:start --host=0.0.0.0 --port=8080 --no-interaction' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stderr_logfile=/dev/stderr' \
    > /etc/supervisor/conf.d/supervisord.conf

# ── Entrypoint (Fixes the "missing .env" and DB Wait issues) ─────────────────
RUN printf '%s\n' \
    '#!/bin/bash' \
    'set -e' \
    'cd /var/www' \
    'if [ -n "$DB_HOST" ]; then' \
    '  until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do echo "Waiting for DB..."; sleep 2; done' \
    'fi' \
    '# Create a dummy .env if it does not exist to prevent artisan errors' \
    'if [ ! -f .env ]; then touch .env; fi' \
    'if [ -z "$APP_KEY" ]; then php artisan key:generate --force; fi' \
    'php artisan migrate --force' \
    'php artisan config:cache' \
    'php artisan route:cache' \
    'php artisan view:cache' \
    'exec supervisord -c /etc/supervisor/conf.d/supervisord.conf' \
    > /entrypoint.sh && chmod +x /entrypoint.sh

EXPOSE 8000 8080
ENTRYPOINT ["/entrypoint.sh"]