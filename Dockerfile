FROM php:8.2-apache

# Install system dependencies and required PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libonig-dev \
        libxml2-dev \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        mbstring \
        dom \
    && (a2dismod -f mpm_event mpm_worker || true) \
    && rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.* \
    && a2enmod mpm_prefork rewrite headers \
    && rm -rf /var/lib/apt/lists/*

# Install Composer from official image
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Install PHP dependencies first for better Docker layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-progress

# Copy application source
COPY . .

# Configure Apache DocumentRoot to public/, enable .htaccess overrides, and validate config
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf \
    && printf '<Directory /var/www/html/public>\n    Options -Indexes +FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\n' > /etc/apache2/conf-available/tcmas.conf \
    && a2enconf tcmas \
    && apache2ctl -t

# Configure production PHP error logging to stderr
RUN printf 'display_errors = Off\nlog_errors = On\nerror_log = /dev/stderr\n' > /usr/local/etc/php/conf.d/tcmas-prod.ini

# Ensure storage directory exists and is owned by www-data
RUN mkdir -p /var/www/html/storage/certificates \
    && chown -R www-data:www-data /var/www/html/storage

EXPOSE 80

# Bind Apache to Railway's dynamic PORT, enforce single MPM, and ensure mounted Volume permissions
CMD ["sh", "-c", "PORT=${PORT:-80}; rm -f /etc/apache2/mods-enabled/mpm_event.* /etc/apache2/mods-enabled/mpm_worker.*; sed -i \"s/Listen 80/Listen ${PORT}/g\" /etc/apache2/ports.conf; sed -i \"s/<VirtualHost \\*:80>/<VirtualHost *:${PORT}>/g\" /etc/apache2/sites-available/000-default.conf; mkdir -p /var/www/html/storage/certificates && chown -R www-data:www-data /var/www/html/storage; exec apache2-foreground"]
