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

RUN yes | pecl install xdebug-3.1.6 \
  && echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
  && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
  && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

RUN docker-php-ext-enable xdebug

# Install Composer
RUN curl --silent --show-error https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# RUN a2enmod rewrite
# RUN a2enmod ssl

RUN apt-get install -y libgmp-dev re2c libmhash-dev libmcrypt-dev file
RUN ln -s /usr/include/x86_64-linux-gnu/gmp.h /usr/local/include/
RUN docker-php-ext-configure gmp
RUN docker-php-ext-install gmp

# RUN pecl install redis

RUN pecl install pcov && docker-php-ext-enable pcov

# ( maybe not needed)
RUN apt-get install -y cron 

RUN apt-get install -y libsqlite3-dev

RUN docker-php-ext-install pdo_sqlite && docker-php-ext-enable pdo_sqlite

#COPY composer.json composer.json
#COPY composer.lock composer.lock
#RUN composer install --no-autoloader

# Enable Apache mod_rewrite for URL rewriting
RUN a2enmod rewrite

# Configure Apache DocumentRoot to point to Laravel's public directory
# and update Apache configuration files
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Copy the application code
COPY . /var/www/html

# Set the working directory
WORKDIR /var/www/html

#RUN mkdir -p storage/app/public
RUN mkdir -p storage/framework/cache/data
RUN mkdir -p storage/framework/sessions
RUN mkdir -p storage/framework/testing
RUN mkdir -p storage/framework/views
RUN mkdir -p storage/logs

# RUN cp .env.example .env

# Set permissions
RUN chmod -R 777 .
RUN chown -R www-data:www-data .

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install project dependencies
RUN composer install
RUN composer dump-autoload

RUN php artisan cache:clear
RUN php artisan view:cache
