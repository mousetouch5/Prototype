# Multi-stage build for React app
FROM node:18-alpine AS frontend-builder
WORKDIR /app
# Copy package files explicitly
COPY frontend/package.json ./package.json
COPY frontend/package-lock.json ./package-lock.json
# Install with legacy peer deps to resolve version conflicts
# Use npm install as fallback if ci fails
RUN npm ci --legacy-peer-deps || npm install --legacy-peer-deps
# Copy the rest of frontend files
COPY frontend/ ./
# Set production environment for React build
ENV NODE_ENV=production
ENV PUBLIC_URL=/
# Don't set REACT_APP_API_URL so it uses window.location.origin in production
RUN npm run build

# Main application stage
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

# Copy .env.example as .env if .env doesn't exist
RUN if [ ! -f /var/www/.env ] && [ -f /var/www/.env.example ]; then \
    cp /var/www/.env.example /var/www/.env; \
fi

# Install dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy React build from frontend-builder stage
COPY --from=frontend-builder /app/build /var/www/public/build

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
# Create .env file from environment variables if it doesnt exist\n\
if [ ! -f /var/www/.env ]; then\n\
    echo "Creating .env file from environment variables..."\n\
    touch /var/www/.env\n\
    echo "APP_NAME=\"${APP_NAME:-Laravel}\"" >> /var/www/.env\n\
    echo "APP_ENV=${APP_ENV:-production}" >> /var/www/.env\n\
    echo "APP_KEY=${APP_KEY:-}" >> /var/www/.env\n\
    echo "APP_DEBUG=${APP_DEBUG:-true}" >> /var/www/.env\n\
    echo "APP_URL=${APP_URL:-http://localhost}" >> /var/www/.env\n\
    echo "" >> /var/www/.env\n\
    echo "LOG_CHANNEL=${LOG_CHANNEL:-stack}" >> /var/www/.env\n\
    echo "LOG_LEVEL=${LOG_LEVEL:-error}" >> /var/www/.env\n\
    echo "" >> /var/www/.env\n\
    # Database configuration with fallbacks\n\
    echo "DB_CONNECTION=${DB_CONNECTION:-pgsql}" >> /var/www/.env\n\
    if [ -n "$DB_HOST" ] && [ "$DB_HOST" != "" ]; then\n\
        echo "Using Render database environment variables..."\n\
        echo "DB_HOST=${DB_HOST}" >> /var/www/.env\n\
        echo "DB_PORT=${DB_PORT:-5432}" >> /var/www/.env\n\
        echo "DB_DATABASE=${DB_DATABASE:-clickup_sync}" >> /var/www/.env\n\
        echo "DB_USERNAME=${DB_USERNAME:-clickup_user}" >> /var/www/.env\n\
        echo "DB_PASSWORD=${DB_PASSWORD}" >> /var/www/.env\n\
    else\n\
        echo "WARNING: No database environment variables found. Using SQLite fallback."\n\
        echo "DB_CONNECTION=sqlite" >> /var/www/.env\n\
        echo "DB_DATABASE=/var/www/database/database.sqlite" >> /var/www/.env\n\
        # Create SQLite database file\n\
        mkdir -p /var/www/database\n\
        touch /var/www/database/database.sqlite\n\
        chown -R www-data:www-data /var/www/database\n\
    fi\n\
    echo "" >> /var/www/.env\n\
    echo "SESSION_DRIVER=${SESSION_DRIVER:-file}" >> /var/www/.env\n\
    echo "CACHE_STORE=${CACHE_STORE:-file}" >> /var/www/.env\n\
    echo "QUEUE_CONNECTION=${QUEUE_CONNECTION:-sync}" >> /var/www/.env\n\
fi\n\
\n\
# Ensure storage permissions\n\
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache\n\
chmod -R 775 /var/www/storage /var/www/bootstrap/cache\n\
\n\
# Generate key if not exists\n\
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then\n\
    echo "Generating application key..."\n\
    php artisan key:generate --force\n\
fi\n\
\n\
# Show database configuration for debugging\n\
echo "Database configuration:"\n\
echo "DB_CONNECTION: $DB_CONNECTION"\n\
echo "DB_HOST: $DB_HOST"\n\
echo "DB_PORT: $DB_PORT"\n\
echo "DB_DATABASE: $DB_DATABASE"\n\
echo "DB_USERNAME: $DB_USERNAME"\n\
echo "DB_PASSWORD: [${#DB_PASSWORD} chars]"\n\
\n\
# Wait for database to be ready (if PostgreSQL)\n\
if [ "$DB_CONNECTION" = "pgsql" ] && [ -n "$DB_HOST" ] && [ "$DB_HOST" != "" ]; then\n\
    echo "Waiting for PostgreSQL to be ready at $DB_HOST:$DB_PORT..."\n\
    for i in {1..30}; do\n\
        if PGPASSWORD=$DB_PASSWORD psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" -d "$DB_DATABASE" -c "SELECT 1" > /dev/null 2>&1; then\n\
            echo "PostgreSQL is ready!"\n\
            break\n\
        else\n\
            echo "PostgreSQL is unavailable (attempt $i/30) - sleeping 2s"\n\
            sleep 2\n\
        fi\n\
    done\n\
elif [ "$DB_CONNECTION" = "sqlite" ]; then\n\
    echo "Using SQLite database - no connection wait needed."\n\
fi\n\
\n\
# Test database connection\n\
echo "Testing database connection..."\n\
php artisan tinker --execute="echo \\DB::connection()->getPDO() ? \"Database connected\" : \"Database failed\";" || echo "Database connection test failed"\n\
\n\
# Run migrations only if database is configured\n\
if [ "$DB_CONNECTION" != "sqlite" ] || [ -f "/var/www/database/database.sqlite" ]; then\n\
    echo "Running migrations..."\n\
    php artisan migrate --force\n\
    echo "Migration status:"\n\
    php artisan migrate:status\n\
    echo "Checking if critical tables exist:"\n\
    php artisan tinker --execute="echo \\Schema::hasTable(\"users\") ? \"users: OK\" : \"users: MISSING\";"\n\
    php artisan tinker --execute="echo \\Schema::hasTable(\"personal_access_tokens\") ? \"tokens: OK\" : \"tokens: MISSING\";"\n\
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