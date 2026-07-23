# Use official PHP 8.2 CLI base image
FROM php:8.2-cli

# Install required PHP extensions for MySQL database connection
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy all project source code into container
COPY . /app

# Set working directory to project root
WORKDIR /app

# Expose default port (Railway will override with $PORT environment variable)
EXPOSE 8080
