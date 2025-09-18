FROM php:8.4-fpm-alpine

# libpq headers for pdo_pgsql
RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-configure pdo_pgsql \
    && docker-php-ext-install pdo_pgsql

WORKDIR /app
