FROM php:8.2-apache

# Install MySQLi extension
RUN docker-php-ext-install mysqli

# Copy project files into the web root
COPY . /var/www/html/

# Set permissions for uploads folder
RUN chmod -R 777 /var/www/html/public/uploads/

EXPOSE 80
