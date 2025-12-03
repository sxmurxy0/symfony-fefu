FROM php:8.4-fpm

WORKDIR /var/www/project

RUN apt-get update && apt-get install -y \
    git zip unzip curl libpq-dev libonig-dev libicu-dev libxml2-dev \
    && docker-php-ext-install pdo pdo_pgsql intl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer