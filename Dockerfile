FROM composer:2 AS composer

FROM php:8.2-fpm

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev default-mysql-client \
    && docker-php-ext-install pdo_mysql bcmath intl zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-interaction --prefer-dist --no-scripts
COPY docker/app/entrypoint.sh /usr/local/bin/slmp-entrypoint

RUN chmod +x /usr/local/bin/slmp-entrypoint

ENTRYPOINT ["slmp-entrypoint"]
CMD ["php-fpm"]
