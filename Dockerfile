# Use PHP 7.4 with FPM (FastCGI Process Manager)
FROM php:7.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache nginx curl git zip unzip

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Copy Nginx and PHP-FPM configuration files
COPY nginx.conf /etc/nginx/nginx.conf
COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Expose ports
EXPOSE 80

# Start PHP-FPM and Nginx together
CMD php-fpm -D && nginx -g 'daemon off;'
