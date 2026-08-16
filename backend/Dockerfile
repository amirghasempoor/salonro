FROM php:8.5.3-fpm-alpine3.23

RUN apk update && apk add --no-cache \
    bash \
    curl \
    libpng \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libwebp-dev \
    libxml2-dev \
    unzip \
    libzip-dev \
    libpq-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install pdo_pgsql gd zip #\
#    && docker-php-ext-install pcntl \
#    && docker-php-ext-install opcache

COPY --from=docker.arvancloud.ir/composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

#COPY docker/opcache-dev.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/entrypoint-dev.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/salonro

COPY . .

RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 9000

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD php -r "exit(@fsockopen('127.0.0.1',9000,\$errno,\$errstr,1)?0:1);"

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
