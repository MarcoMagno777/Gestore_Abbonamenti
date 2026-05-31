FROM php:8.3-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install mysqli \
    && a2enmod rewrite headers

RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

RUN sed -i 's#/var/www/html#/var/www/html#g' /etc/apache2/sites-available/000-default.conf

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY entrypoint-php.sh /entrypoint-php.sh
RUN chmod +x /entrypoint-php.sh

EXPOSE 80

CMD ["/entrypoint-php.sh"]