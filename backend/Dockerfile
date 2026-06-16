FROM php:8.5.3-fpm-alpine3.23

# Install common PHP extension dependencies
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
    && docker-php-ext-install pdo_pgsql gd zip

# Set the working directory
WORKDIR /var/www/salonro

COPY . .

RUN chmod -R 777 /var/www/salonro/storage /var/www/salonro/bootstrap/cache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# Default command
CMD composer install ;\
    composer dump-autoload --optimize --classmap-authoritative ;\
    php-fpm
