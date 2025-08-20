#!/usr/bin/env bash

set -e

echo "Building ClickUp Sync Dashboard..."

# Install PHP dependencies
echo "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader

# Generate application key if not exists
if [ -z "$APP_KEY" ]; then
    echo "Generating application key..."
    php artisan key:generate --force
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Cache configuration
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Install Node.js dependencies and build frontend
echo "Building frontend..."
cd frontend
npm ci
npm run build

echo "Build completed successfully!"