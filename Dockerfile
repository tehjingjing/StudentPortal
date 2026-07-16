# Use official PHP 8.2 image
FROM php:8.2-cli

# Install Composer binary
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install mysql extension required for your database connection
RUN docker-php-ext-install pdo_mysql mysqli

# Set working directory inside container
WORKDIR /app

# Copy all project code into container
COPY . .

# Install PHPMailer and other composer dependencies during build
RUN composer install --no-dev

# Start PHP built-in dev server, listen Render assigned port
CMD php -S 0.0.0.0:$PORT
