# Multi-stage build for better layer caching
FROM node:18-bullseye-slim AS assets

WORKDIR /app

COPY package.json package-lock.json webpack.mix.js ./
COPY public ./public
COPY resources ./resources

RUN npm ci --legacy-peer-deps --no-audit --no-fund \
    && NODE_OPTIONS="--max-old-space-size=768" npm run build

FROM php:8.2-apache AS base

# Install system dependencies and PHP extensions in single layer
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    gnupg \
    && docker-php-ext-install -j$(nproc) pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite headers \
    && apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Install Node.js and npm (always available for runtime asset building)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && \
    apt-get update && \
    apt-get install -y --no-install-recommends nodejs && \
    npm cache clean --force && \
    apt-get purge -y --auto-remove -o APT::AutoRemove::RecommendsImportant=false && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /root/.npm

# Configure Apache - using printf instead of echo to avoid shell issues
RUN printf "ServerName localhost\n" >> /etc/apache2/apache2.conf && \
    sed -i 's|DocumentRoot /var/www/html|DocumentRoot /var/www/html/public|g' /etc/apache2/sites-available/000-default.conf && \
    printf '<Directory /var/www/html/public>\n    Options Indexes FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n' >> /etc/apache2/sites-available/000-default.conf

# Configure PHP for production with resource limits
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "max_execution_time = 30" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "max_input_time = 30" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "post_max_size = 32M" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "upload_max_filesize = 32M" >> /usr/local/etc/php/conf.d/production.ini

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy only composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies (cached unless composer files change)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy startup script and make executable
COPY bin/start-apache.sh /usr/local/bin/start-apache.sh
RUN chmod +x /usr/local/bin/start-apache.sh

# Copy application code (invalidates cache when code changes)
COPY . .

# Copy prebuilt frontend assets into the runtime image so production deployments
# can pull a tested image instead of rebuilding assets on the VPS.
COPY --from=assets /app/public/js /var/www/html/public/js
COPY --from=assets /app/public/mix-manifest.json /var/www/html/public/mix-manifest.json

# Recreate Laravel runtime directories excluded from the Docker build context
RUN mkdir -p \
    /var/www/html/bootstrap/cache \
    /var/www/html/storage/app/public \
    /var/www/html/storage/app/backups \
    /var/www/html/storage/framework/cache \
    /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/logs

# Set proper ownership and permissions for web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Use startup script as entrypoint
CMD ["/usr/local/bin/start-apache.sh"]
