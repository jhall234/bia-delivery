FROM wordpress:latest

RUN pecl install xdebug

COPY php.ini /usr/local/etc/php/
COPY xdebug.ini /usr/local/etc/php/conf.d/

RUN docker-php-ext-enable xdebug
