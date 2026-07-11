FROM dunglas/frankenphp:1.4.4-php8.4-alpine AS php

RUN apk update && \
    apk upgrade && \
    apk add --no-cache \
    dcron \
    curl \
    icu-dev \
    postgresql-dev \
    libzip-dev \
    git \
    unzip \
    nodejs \
    npm

RUN docker-php-ext-configure intl && \
    docker-php-ext-install \
    pdo \
    pdo_pgsql \
    opcache \
    zip \
    pcntl

RUN apk add --no-cache autoconf g++ make linux-headers && \
    pecl install redis xdebug && \
    docker-php-ext-enable redis xdebug

RUN echo "xdebug.mode=coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

CMD ["sh", "-c", "composer install --no-interaction && npm install && npm run build && php artisan octane:start --server=frankenphp --host=127.0.0.1 --port=8000"]
