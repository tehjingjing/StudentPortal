FROM php:8.2-cli

# install common php extensions needed for mysql connection
RUN docker-php-ext-install mysqli pdo pdo_mysql

# copy whole project files into container
COPY . /app
WORKDIR /app

# expose runtime port, actual port is provided by railway env variable
EXPOSE ${PORT}
