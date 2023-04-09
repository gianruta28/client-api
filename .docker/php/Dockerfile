ARG PHP_VERSION=7.4

FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add --update \
    zip \
    unzip \
    curl \
    nodejs \
    yarn

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer && \
    ln -s $(composer config --global home) /root/composer
ENV PATH=$PATH:/root/composer/vendor/bin COMPOSER_ALLOW_SUPERUSER=1 PHP_MEMORY_LIMIT=512M

COPY .docker/php/config/php.ini /usr/local/etc/php/php.ini
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug-3.0.0 \
    && docker-php-ext-enable xdebug

COPY .docker/php/config/xdebug.ini /usr/local/etc/php/conf.d

RUN docker-php-ext-install mysqli pdo pdo_mysql

WORKDIR /var/www/html

COPY .docker/php/config/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod 777 /usr/local/bin/docker-entrypoint.sh

RUN chmod 777 -R /var/www/html

EXPOSE 9003
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

CMD ["php-fpm"]