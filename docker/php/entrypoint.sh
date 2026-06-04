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

# Local Docker development can run on Windows/WSL bind mounts or Docker volumes.
# Make Laravel's writable directories writable by the PHP-FPM worker user.
chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
chmod -R a+rwX storage bootstrap/cache 2>/dev/null || true

exec php-fpm
