# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# YASSAMI — image PHP-FPM (Laravel + MySQL) pour le développement local
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-alpine AS app

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
        pdo_mysql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache \
    && apk add --no-cache \
        bash \
        git \
        curl \
        unzip \
        mysql-client \
        su-exec

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Aligne www-data sur l'uid 1000 (fichiers créés lisibles depuis l'hôte)
RUN deluser www-data 2>/dev/null || true; \
    adduser -D -u 1000 -g www-data -s /bin/bash www-data

WORKDIR /var/www/html

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Le code est monté en volume par docker-compose.yml
EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
