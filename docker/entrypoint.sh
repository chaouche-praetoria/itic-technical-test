#!/bin/sh
set -e

# Setup basic directories for both app and worker
echo "==> Preparing storage directories..."
mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs
mkdir -p public/build

# Ensure that volume-mounted directories are owned by laravel
# This is crucial for VPS environments where volumes might be owned by root
# We use || true to prevent the script from crashing if some permissions cannot be changed
echo "==> Setting permissions (this may take a moment)..."
chown -R laravel:www-data storage bootstrap/cache public/build || echo "    Warning: chown failed, continuing..."
chmod -R 775 storage bootstrap/cache public/build 2>/dev/null || echo "    Warning: chmod failed, continuing..."

# Check the role of the container (default to 'app' if not specified)
ROLE=${1:-app}

# Check for APP_KEY
if [ -z "$APP_KEY" ]; then
    echo "    Warning: APP_KEY is not set. This might cause issues in production."
fi

if [ "$ROLE" = "app" ]; then
    echo "==> Role: Application"
    
    echo "    Waiting for database..."
    until su-exec laravel php artisan db:show --json > /dev/null 2>&1; do
      echo "    Database not ready, retrying in 3s..."
      sleep 3
    done

    echo "    Running migrations..."
    su-exec laravel php artisan migrate --force --no-interaction || {
      echo "    Migration failed, retrying once..."
      sleep 5
      su-exec laravel php artisan migrate --force --no-interaction
    }

    echo "    Seeding database (first run only)..."
    su-exec laravel php artisan db:seed --force --class=RoleSeeder --no-interaction 2>/dev/null || true
    su-exec laravel php artisan db:seed --force --class=AdminUserSeeder --no-interaction 2>/dev/null || true
    su-exec laravel php artisan db:seed --force --class=DomainSeeder --no-interaction 2>/dev/null || true
    su-exec laravel php artisan db:seed --force --class=QuestionSeeder --no-interaction 2>/dev/null || true

    if [ "$APP_ENV" != "local" ]; then
        echo "    Syncing build assets to public volume..."
        # Using -rlD instead of -a to avoid metadata conflicts (ownership/times)
        rsync -rlD --delete public_assets/build/ public/build/ || echo "    Warning: rsync build assets failed"
        chown -R laravel:www-data public/build || true
        
        echo "    Caching config & routes for production..."
        su-exec laravel php artisan config:cache
        su-exec laravel php artisan route:cache
        su-exec laravel php artisan view:cache 2>/dev/null || echo "    view:cache skipped"
    else
        echo "    Dev mode: skipping cache..."
        su-exec laravel php artisan config:clear
        su-exec laravel php artisan route:clear
    fi

    echo "==> Starting PHP-FPM..."
    exec php-fpm -F

elif [ "$ROLE" = "worker" ]; then
    echo "==> Role: Queue Worker"
    
    if [ "$APP_ENV" != "local" ]; then
        echo "    Caching config for worker..."
        su-exec laravel php artisan config:cache
    fi

    echo "==> Starting Queue Worker..."
    # Processing the default queue with reasonable limits
    exec su-exec laravel php artisan queue:work --verbose --tries=3 --timeout=90

else
    echo "==> Unknown role: $ROLE. Executing command directly..."
    exec su-exec laravel "$@"
fi
