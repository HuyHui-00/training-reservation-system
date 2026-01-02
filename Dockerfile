FROM php:8.2-apache

RUN docker-php-ext-install mysqli pdo pdo_mysql
    
# Copy the application files into the Apache document root
COPY ./src/ /var/www/html/

EXPOSE 80
