# Base image with PHP 7.4 and FPM
FROM php:7.4-fpm

# Install necessary dependencies
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
COPY php-fpm.conf /etc/php/7.4/fpm/php-fpm.conf

# Expose ports
EXPOSE 80

# Start both Nginx and PHP-FPM together
CMD service php7.4-fpm start && nginx -g "daemon off;"
