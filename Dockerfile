FROM php:8.2-fpm

# Set environment variables
ENV DEBIAN_FRONTEND=noninteractive

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    postgresql-client \
    zip \
    unzip \
    nginx \
    supervisor

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Get Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application files
COPY . /var/www

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy nginx configuration
COPY docker/nginx.conf /etc/nginx/sites-available/default

# Copy PHP-FPM configuration
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Copy supervisor configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create storage and bootstrap/cache directories with proper permissions
RUN mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# Create startup script
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
# Update nginx port configuration\n\
sed -i "s/listen 80;/listen ${PORT:-80};/g" /etc/nginx/sites-available/default\n\
sed -i "s/listen \[::\]:80;/listen \[::\]:${PORT:-80};/g" /etc/nginx/sites-available/default\n\
\n\
# Ensure storage permissions\n\
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache\n\
chmod -R 775 /var/www/storage /var/www/bootstrap/cache\n\
\n\
# Generate key if not exists\n\
if [ -z "$APP_KEY" ]; then\n\
    php artisan key:generate --force\n\
fi\n\
\n\
# Wait for database to be ready (if PostgreSQL)\n\
if [ "$DB_CONNECTION" = "pgsql" ]; then\n\
    echo "Waiting for PostgreSQL to be ready..."\n\
    until PGPASSWORD=$DB_PASSWORD psql -h "$DB_HOST" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1; do\n\
        echo "PostgreSQL is unavailable - sleeping"\n\
        sleep 2\n\
    done\n\
    echo "PostgreSQL is ready!"\n\
fi\n\
\n\
# Run migrations only if database is configured\n\
if [ "$DB_CONNECTION" != "sqlite" ] || [ -f "/var/www/database/database.sqlite" ]; then\n\
    php artisan migrate --force || echo "Migration failed, continuing..."\n\
fi\n\
\n\
# Cache configurations only after database is ready\n\
php artisan config:cache\n\
php artisan route:cache\n\
php artisan view:cache\n\
\n\
# Start supervisord\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /start.sh && chmod +x /start.sh

# Start services
CMD ["/start.sh"]