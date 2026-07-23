FROM php:8.2-cli

# Install required extensions: database + mail support
RUN docker-php-ext-install mysqli pdo_mysql mbstring
RUN docker-php-ext-enable openssl

# Copy project files into container
COPY . /app
WORKDIR /app

EXPOSE 8080
