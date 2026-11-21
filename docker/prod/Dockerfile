# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# YASSAMI — images de PRODUCTION
#   cible « app » : PHP-FPM avec le code, vendor (--no-dev) et les assets compilés
#   cible « web » : Nginx avec le dossier public/ (assets) ; PHP est servi par « app »
# Construit par docker-compose.prod.yml. Le dev local garde ./Dockerfile.
# ---------------------------------------------------------------------------

# ------------------------------------------------------------ PHP de base --
FROM php:8.4-fpm-alpine AS php-base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions pdo_mysql mbstring exif pcntl bcmath gd zip intl opcache \
    && apk add --no-cache bash mysql-client su-exec

WORKDIR /var/www/html

# ------------------------------------------------- Dépendances Composer --
FROM php-base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --no-progress --no-scripts --no-autoloader

COPY . .
# dump-autoload lance aussi « package:discover » (scripts composer.json).
RUN composer dump-autoload --no-dev --optimize

# ------------------------------------------------------- Assets (Vite) --
FROM node:22-alpine AS assets

WORKDIR /var/www/html
COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
# Les vues de pagination Laravel sont scannées par Tailwind (@source dans app.css).
COPY --from=vendor /var/www/html/vendor/laravel/framework/src/Illuminate/Pagination/resources/views vendor/laravel/framework/src/Illuminate/Pagination/resources/views
RUN npm run build

# ------------------------------------------------------- Image « app » --
FROM php-base AS app

COPY docker/prod/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/prod/entrypoint.sh /usr/local/bin/entrypoint

COPY --from=vendor --chown=www-data:www-data /var/www/html /var/www/html
COPY --from=assets --chown=www-data:www-data /var/www/html/public/build /var/www/html/public/build

RUN chmod +x /usr/local/bin/entrypoint \
    && rm -f public/hot \
    && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# ------------------------------------------------------- Image « web » --
FROM nginx:1.27-alpine AS web

COPY docker/prod/nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=vendor /var/www/html/public /var/www/html/public
COPY --from=assets /var/www/html/public/build /var/www/html/public/build

# /storage → photos téléversées (volume partagé avec « app », monté en lecture seule).
RUN rm -f /var/www/html/public/hot /var/www/html/public/storage \
    && ln -s ../storage/app/public /var/www/html/public/storage
