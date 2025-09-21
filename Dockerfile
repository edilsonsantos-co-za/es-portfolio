# Multi-stage build for better layer caching
FROM php:8.2-apache AS base

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y \
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
    vim \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && a2enmod rewrite headers \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js and npm (always available for runtime asset building)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get update \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure Apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf \
    && sed -i 's/DocumentRoot \/var\/www\/html/DocumentRoot \/var\/www\/html\/public/g' /etc/apache2/sites-available/000-default.conf

# Add custom Apache config
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/sites-available/000-default.conf

# Configure PHP for production
RUN echo "display_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "display_startup_errors = Off" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "error_reporting = E_ALL & ~E_DEPRECATED & ~E_STRICT" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "log_errors = On" >> /usr/local/etc/php/conf.d/production.ini \
    && echo "memory_limit = 256M" >> /usr/local/etc/php/conf.d/production.ini

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

# Set proper ownership and permissions for web server
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Use startup script as entrypoint
CMD ["/usr/local/bin/start-apache.sh"]