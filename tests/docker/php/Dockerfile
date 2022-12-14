FROM php:7.0

RUN docker-php-ext-install mysqli

COPY wait-for-it.sh /wait-for-it.sh
RUN chmod +x /wait-for-it.sh