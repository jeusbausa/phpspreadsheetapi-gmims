# Use the official PHP image with Apache
FROM php:7.4-apache

# Set working directory
WORKDIR /var/www/html

# Copy application files to the container
COPY . /var/www/html/

# Enable Apache mod_rewrite (useful for frameworks like Laravel)
RUN a2enmod rewrite

# Install necessary PHP extensions for PHPSpreadsheet
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip && \
    docker-php-ext-install zip pdo pdo_mysql \
    && docker-php-ext-enable zip

# Set the ServerName to suppress warning
RUN echo "ServerName spreadsheet.gmimsys.com" >> /etc/apache2/apache2.conf

# Expose port 80
EXPOSE 80

# Start Apache server
CMD ["apache2-foreground"]
