FROM php:7.4-apache

# Set working directory
WORKDIR /var/www/html

# Copy application files to the container
COPY . /var/www/html/

# Copy Apache configuration
COPY apache.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install necessary PHP extensions for PHPSpreadsheet
RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    && docker-php-ext-install zip pdo pdo_mysql \
    && docker-php-ext-enable zip

# Set the ServerName to suppress warnings
RUN echo "ServerName spreadsheet.gmimsys.com" >> /etc/apache2/apache2.conf

# Expose port based on Railway's dynamic port assignment
ENV PORT=8080
EXPOSE 8080

# Update Apache to listen on Railway's port
RUN sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf

# Start Apache server
CMD ["apache2-foreground"]
