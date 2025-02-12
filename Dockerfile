# Use official PHP 7.4 FPM base image
FROM php:7.4-fpm

# Install Nginx and necessary dependencies
RUN apt-get update && apt-get install -y \
    nginx \
    libzip-dev unzip \
    && docker-php-ext-install zip pdo pdo_mysql

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy configuration files
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.d/zz-docker.conf

# Expose HTTP port
EXPOSE 80

# Command to start both Nginx & PHP-FPM
CMD ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]
