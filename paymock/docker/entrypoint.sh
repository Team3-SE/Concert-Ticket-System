#!/bin/sh
set -e

# Create sqlite db if missing
mkdir -p /var/www/html/database
touch /var/www/html/database/paymock.sqlite
chown -R www-data:www-data /var/www/html/database

# Create log directory for supervisord
mkdir -p /var/log/supervisor

# Generate APP_KEY if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    cd /var/www/html && php artisan key:generate --force
fi

# Copy .env if not exists
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
fi

# Run package discover and migrations
cd /var/www/html && php artisan package:discover --ansi
cd /var/www/html && php artisan migrate --force --no-interaction

# Clear and cache config
cd /var/www/html && php artisan config:clear
cd /var/www/html && php artisan route:clear

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod 664 /var/www/html/database/paymock.sqlite

exec "$@"
