# Use Alpine for a lightweight PHP 7.4 + Nginx setup
FROM php:7.4-fpm-alpine

# Install necessary dependencies
RUN apk add --no-cache nginx nodejs npm libmysqlclient-dev \
    && docker-php-ext-install pdo pdo_mysql

# Set up working directory
WORKDIR /var/www/html

# Copy project files
COPY . .

# Ensure necessary directories exist
RUN mkdir -p /var/log/nginx /var/cache/nginx

# Copy Nginx and PHP-FPM configurations (if you have them)
COPY ./nginx.conf /etc/nginx/nginx.conf
COPY ./php-fpm.conf /usr/local/etc/php-fpm.conf

# Expose ports
EXPOSE 80

# Start services
CMD ["sh", "-c", "nginx && php-fpm"]
