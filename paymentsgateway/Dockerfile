FROM php:8.3-fpm-alpine AS base

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    sqlite \
    sqlite-dev \
    && docker-php-ext-install pdo pdo_sqlite zip gd bcmath opcache \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for layer caching
COPY composer.json composer.lock* ./
RUN if [ -f composer.lock ]; then \
        composer install --no-dev --no-scripts --no-autoloader --prefer-dist; \
    else \
        composer update --no-dev --no-scripts --no-autoloader --prefer-dist; \
    fi

# Copy app files
COPY . .

# Create required directories and set permissions before autoloader
RUN mkdir -p /var/www/html/bootstrap/cache \
             /var/www/html/storage/framework/cache \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/database \
    && touch /var/www/html/database/paymock.sqlite \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Generate autoloader without post-autoload scripts during image build
RUN composer dump-autoload --optimize --no-scripts

# Nginx config
COPY docker/nginx.conf /etc/nginx/nginx.conf

# Supervisord config
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Entrypoint
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
