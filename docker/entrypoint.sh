#!/bin/bash
set -e

cd /var/www

echo "──────────────────────────────────────────"
echo " Ticket Center API — container starting"
echo "──────────────────────────────────────────"

# ── Wait for MySQL to be ready ────────────────────────────────────────────────
if [ -n "$DB_HOST" ]; then
  echo "⏳ Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}…"
  until mysqladmin ping -h"$DB_HOST" -P"${DB_PORT:-3306}" -u"$DB_USERNAME" -p"$DB_PASSWORD" --silent 2>/dev/null; do
    sleep 2
  done
  echo "✅ MySQL is ready"
fi

# ── Generate APP_KEY if not set ───────────────────────────────────────────────
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "base64:" ]; then
  echo "🔑 Generating APP_KEY…"
  php artisan key:generate --force
fi

# ── Cache config / routes / views ────────────────────────────────────────────
echo "📦 Caching config…"
php artisan config:cache
php artisan route:cache
php artisan view:cache

# ── Run migrations ────────────────────────────────────────────────────────────
echo "🗄️  Running migrations…"
php artisan migrate --force

# ── Seed roles if the roles table is empty ────────────────────────────────────
ROLE_COUNT=$(php artisan tinker --execute="echo \Spatie\Permission\Models\Role::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$ROLE_COUNT" = "0" ]; then
  echo "🌱 Seeding roles…"
  php artisan db:seed --class=RoleSeeder --force 2>/dev/null || echo "  (no RoleSeeder found — skipping)"
fi

# ── Start supervisor (php-fpm + nginx + reverb) ───────────────────────────────
echo "🚀 Starting services…"
mkdir -p /var/log/supervisor
exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
