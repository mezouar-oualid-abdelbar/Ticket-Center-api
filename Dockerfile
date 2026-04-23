# =============================================================================
# Ticket Center — API  (PHP 8.4 · Laravel 12 · Nginx · Supervisord)
# =============================================================================
FROM php:8.4-fpm-alpine

# ── System packages ───────────────────────────────────────────────────────────
RUN apk add --no-cache \
        bash curl nginx supervisor \
        libpng-dev libxml2-dev libzip-dev \
        icu-dev oniguruma-dev \
        linux-headers \
        zip unzip mysql-client

# ── PHP extensions ────────────────────────────────────────────────────────────
RUN docker-php-ext-install \
        pdo_mysql mbstring exif pcntl bcmath gd zip sockets intl opcache

# ── Opcache ───────────────────────────────────────────────────────────────────
RUN { echo "opcache.enable=1"; \
      echo "opcache.memory_consumption=128"; \
      echo "opcache.max_accelerated_files=10000"; \
      echo "opcache.validate_timestamps=0"; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# ── PHP dependencies ──────────────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist

COPY . .

# ── Autoloader ────────────────────────────────────────────────────────────────
# --no-scripts prevents package:discover from running at build time
# (it needs a real env + DB, so we run it in the entrypoint instead)
RUN composer dump-autoload --optimize --no-dev --no-scripts

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www \
 && chmod -R 775 storage bootstrap/cache \
 && mkdir -p storage/logs \
              storage/framework/cache \
              storage/framework/sessions \
              storage/framework/views

# ── Nginx config ──────────────────────────────────────────────────────────────
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
    '        set $cors_origin "";' \
    '        if ($http_origin ~* "^http://(localhost|127\.0\.0\.1)(:[0-9]+)?$") {' \
    '            set $cors_origin $http_origin;' \
    '        }' \
    '        add_header Access-Control-Allow-Origin  $cors_origin always;' \
    '        add_header Access-Control-Allow-Methods "GET,POST,PUT,PATCH,DELETE,OPTIONS" always;' \
    '        add_header Access-Control-Allow-Headers "Authorization,Content-Type,Accept,X-Requested-With,X-Socket-Id" always;' \
    '        add_header Access-Control-Allow-Credentials "true" always;' \
    '        add_header Access-Control-Max-Age 86400 always;' \
    '        if ($request_method = OPTIONS) {' \
    '            return 204;' \
    '        }' \
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

# ── Supervisord config ────────────────────────────────────────────────────────
# Only manages php-fpm + nginx.
# reverb and queue are separate containers with their own entrypoint overrides.
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
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    '' \
    '[program:nginx]' \
    'command=nginx -g "daemon off;"' \
    'autostart=true' \
    'autorestart=true' \
    'stdout_logfile=/dev/stdout' \
    'stdout_logfile_maxbytes=0' \
    'stderr_logfile=/dev/stderr' \
    'stderr_logfile_maxbytes=0' \
    > /etc/supervisor/conf.d/supervisord.conf

# ── Entrypoint script ─────────────────────────────────────────────────────────
RUN printf '%s\n' \
    '#!/bin/bash' \
    'set -e' \
    'cd /var/www' \
    '' \
    '# Wait for MySQL' \
    'if [ -n "$DB_HOST" ]; then' \
    '  echo "Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."' \
    '  until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do' \
    '    sleep 2' \
    '  done' \
    '  echo "MySQL is ready."' \
    'fi' \
    '' \
    '# Generate app key if missing' \
    'if [ -z "$APP_KEY" ]; then' \
    '  php artisan key:generate --force' \
    'fi' \
    '' \
    '# Run package discovery at runtime (needs real env + packages)' \
    'php artisan package:discover --ansi || true' \
    '' \
    '# Run migrations' \
    'php artisan migrate --force' \
    '' \
    '# Cache for performance' \
    'php artisan config:cache' \
    'php artisan route:cache' \
    'php artisan view:cache' \
    '' \
    '# Start supervisord (php-fpm + nginx)' \
    'exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf'

EXPOSE 8000
