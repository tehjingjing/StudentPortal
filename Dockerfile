FROM php:8.2-cli

# Install mysqli、pdo_mysql extensions for database connection
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project code to container
COPY . /app
WORKDIR /app

EXPOSE ${PORT}
