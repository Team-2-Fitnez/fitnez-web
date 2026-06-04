FROM php:8.4-fpm

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    libpq-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_pgsql zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY entrypoint.sh /usr/local/bin/fitnez-entrypoint
RUN chmod +x /usr/local/bin/fitnez-entrypoint

ENTRYPOINT ["/usr/local/bin/fitnez-entrypoint"]
