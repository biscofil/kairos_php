# Set the base image for subsequent instructions
FROM biscofil/php_ext:7.4

# Configure Apache DocumentRoot to point to Laravel's public directory
# and update Apache configuration files
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN echo "zend_extension=$(find /usr/local/lib/php/extensions/ -name xdebug.so)" > /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_enable=on" >> /usr/local/etc/php/conf.d/xdebug.ini \
    && echo "xdebug.remote_autostart=off" >> /usr/local/etc/php/conf.d/xdebug.ini

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

# Install project dependencies
RUN composer install --no-scripts --no-autoloader
