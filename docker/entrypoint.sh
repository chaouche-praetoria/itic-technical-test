#!/bin/sh
set -e

echo "==> Waiting for database..."
until php artisan db:show --json > /dev/null 2>&1; do
  echo "    Database not ready, retrying in 3s..."
  sleep 3
done

echo "==> Running migrations..."
php artisan migrate --force --no-interaction || {
  echo "    Migration failed, retrying once..."
  sleep 5
  php artisan migrate --force --no-interaction
}

echo "==> Seeding database (first run only)..."
php artisan db:seed --force --class=AdminUserSeeder --no-interaction 2>/dev/null || true
php artisan db:seed --force --class=RoleSeeder --no-interaction 2>/dev/null || true
php artisan db:seed --force --class=DomainSeeder --no-interaction 2>/dev/null || true
php artisan db:seed --force --class=QuestionSeeder --no-interaction 2>/dev/null || true

echo "==> Preparing storage directories..."
mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs
chmod -R 775 storage/framework bootstrap/cache 2>/dev/null || true

if [ "$APP_ENV" != "local" ]; then
  echo "==> Caching config & routes for production..."
  php artisan config:cache
  php artisan route:cache
  php artisan view:cache 2>/dev/null || echo "    view:cache skipped (will compile on demand)"
else
  echo "==> Dev mode: skipping cache..."
  php artisan config:clear
  php artisan route:clear
fi

echo "==> Starting PHP-FPM..."
exec php-fpm
