FROM php:8.1-apache

RUN docker-php-ext-install mysqli

COPY blue-index.php /var/www/html/index.php

# 80端口默认暴露
EXPOSE 80
