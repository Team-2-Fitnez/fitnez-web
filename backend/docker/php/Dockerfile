FROM php:8.4-fpm

RUN apt-get update \
    && apt-get install -y git unzip zip libpq-dev libzip-dev libpng-dev libjpeg-dev libwebp-dev \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql zip gd \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY backend/ ./

RUN test -f vendor/autoload.php \
    && mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public storage/app/private storage/app/private/trainer-applications storage/app/public/payment-proofs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R a+rwX storage bootstrap/cache
