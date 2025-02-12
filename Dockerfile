# Use PHP 7.4 with FPM (FastCGI Process Manager)
FROM php:7.4-fpm-alpine

# Install system dependencies
RUN apk add --no-cache nginx curl git zip unzip

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Set permissions
RUN chown -R nobody:nobody /app && chmod -R 755 /app

# Copy config files
# COPY nginx.conf /etc/nginx/nginx.conf
# COPY php-fpm.conf /usr/local/etc/php-fpm.d/www.conf

# Expose necessary port
EXPOSE 80

# Start PHP-FPM and Nginx together
CMD php-fpm -D && nginx -g 'daemon off;'
