FROM php:8.4-fpm-alpine

# System deps
RUN apk add --no-cache \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    bash

# PHP extensions
RUN docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Redis extension
RUN apk add --no-cache $PHPIZE_DEPS \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && apk del $PHPIZE_DEPS

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# php.ini overrides
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini

# Create non-root user matching host uid (overridable at build time)
ARG UID=1000
ARG GID=1000
RUN addgroup -g ${GID} www && adduser -u ${UID} -G www -s /bin/bash -D www

WORKDIR /var/www

USER www
