# Set the base image for subsequent instructions
FROM php:7.4-apache

RUN apt-get update

RUN apt-get install -y --no-install-recommends apt-utils libzip-dev zip unzip \
    libmcrypt-dev zlib1g-dev libicu-dev g++ supervisor ca-certificates

RUN docker-php-ext-configure intl
RUN docker-php-ext-install intl

# for phpunit timeout
RUN docker-php-ext-install pcntl

RUN pecl install mcrypt-1.0.3 && docker-php-ext-enable mcrypt

# Install needed extensions
# Here you can install any other extension that you need during the test and deployment process
RUN docker-php-ext-install pdo_mysql zip

RUN yes | pecl install xdebug \
    && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN docker-php-ext-enable xdebug

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN a2enmod rewrite
RUN a2enmod ssl

RUN apt-get install -y libgmp-dev re2c libmhash-dev libmcrypt-dev file
RUN ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/local/include/
RUN docker-php-ext-configure gmp
RUN docker-php-ext-install gmp

RUN pecl install redis

RUN pecl install pcov && docker-php-ext-enable pcov

RUN apt-get install -y cron

RUN apt-get install -y libsqlite3-dev

RUN docker-php-ext-install pdo_sqlite && docker-php-ext-enable pdo_sqlite

# comment out for default user (CI)
RUN addgroup app_user_g -u 1000
RUN useradd -m -r -u 1000 -g app_user_g app_user
USER app_user
# comment out for default user (CI)
