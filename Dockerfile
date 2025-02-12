# Use PHP 7.4 with FPM and Alpine
FROM php:7.4-fpm-alpine

# Install necessary dependencies
RUN apk add --no-cache nginx nodejs npm mariadb-dev \
    && docker-php-ext-install pdo pdo_mysql

# Set up working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html

# Debugging: Show files before copying
RUN ls -lah ./

# Copy Nginx and PHP-FPM configurations
COPY php-fpm.conf /usr/local/etc/php-fpm.conf
COPY nginx.conf /etc/nginx/nginx.conf

# Ensure necessary directories exist
RUN mkdir -p /var/log/nginx /var/cache/nginx

# Set permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port
EXPOSE 80

# Start services
CMD ["sh", "-c", "nginx && php-fpm"]
