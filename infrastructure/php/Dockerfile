FROM php:8.4-fpm-alpine as php

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
        pdo_mysql \
        pdo_pgsql \
        intl \
        opcache \
        bcmath \
        zip \
        pcntl

RUN apk add --no-cache autoconf g++ make && \
    pecl install redis && \
    docker-php-ext-enable redis

WORKDIR /var/www