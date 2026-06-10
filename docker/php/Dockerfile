FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    dos2unix \
    netcat-openbsd \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo pdo_pgsql zip gd

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY entrypoint.sh /usr/local/bin/fitnez-entrypoint
RUN dos2unix /usr/local/bin/fitnez-entrypoint \
    && chmod +x /usr/local/bin/fitnez-entrypoint

ENTRYPOINT ["/usr/local/bin/fitnez-entrypoint"]
