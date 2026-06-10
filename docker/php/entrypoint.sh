#!/bin/sh
set -e

cd /var/www/html

mkdir -p \
  storage/logs \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/app/public \
  storage/app/private \
  storage/app/private/trainer-applications \
  storage/app/public/payment-proofs \
  bootstrap/cache

touch storage/logs/laravel.log

# Fix .env CRLF if present (Windows host)
if [ -f ".env" ]; then
  sed -i 's/\r//' .env
fi

# Install composer dependencies if vendor/autoload.php is missing
if [ ! -f "vendor/autoload.php" ]; then
  echo "Running composer install..."
  composer install --no-interaction --prefer-dist
fi

# Read DB config from .env
DB_HOST_VAL=$(grep "^DB_HOST=" .env | cut -d= -f2 | tr -d '\r')
DB_PORT_VAL=$(grep "^DB_PORT=" .env | cut -d= -f2 | tr -d '\r')
DB_DATABASE_VAL=$(grep "^DB_DATABASE=" .env | cut -d= -f2 | tr -d '\r')
DB_USERNAME_VAL=$(grep "^DB_USERNAME=" .env | cut -d= -f2 | tr -d '\r')
DB_PASSWORD_VAL=$(grep "^DB_PASSWORD=" .env | cut -d= -f2 | tr -d '\r')

# Generate app key if APP_KEY is empty
APP_KEY_VAL=$(grep "^APP_KEY=" .env | cut -d= -f2 | tr -d '\r')
if [ -z "$APP_KEY_VAL" ]; then
  php artisan key:generate --no-interaction 2>/dev/null || true
fi

# Wait for database to be ready using TCP check
echo "Waiting for database at ${DB_HOST_VAL}:${DB_PORT_VAL}..."
RETRIES=30
until nc -z "$DB_HOST_VAL" "$DB_PORT_VAL" 2>/dev/null; do
  RETRIES=$((RETRIES - 1))
  if [ $RETRIES -le 0 ]; then
    echo "Database connection timed out. Proceeding anyway..."
    break
  fi
  sleep 2
  echo "Database not ready yet, retrying ($RETRIES left)..."
done
echo "Database is ready."

# Run migrations
php artisan migrate --no-interaction --force 2>/dev/null || true

# Clear config cache
php artisan config:clear 2>/dev/null || true

# Make Laravel's writable directories writable by the PHP-FPM worker user.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true

exec php-fpm
