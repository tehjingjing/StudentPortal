FROM php:8.2-cli

# Install required PHP extensions for MySQL database connection
RUN docker-php-ext-install mysqli pdo_mysql

# Copy all project source code into container
COPY . /app
WORKDIR /app

# Expose default port for Railway proxy
EXPOSE 8080
