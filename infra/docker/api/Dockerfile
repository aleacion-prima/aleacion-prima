FROM dunglas/frankenphp:1-php8.4 AS base

RUN install-php-extensions pdo_pgsql mongodb pcntl intl zip opcache

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS dev

ENV COMPOSER_HOME=/tmp/composer
RUN mkdir -p /tmp/composer /tmp/composer-cache \
    && chmod 777 /tmp/composer /tmp/composer-cache

CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]

FROM base AS prod

ENV APP_ENV=production \
    APP_DEBUG=false \
    SERVER_NAME=:8000

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative \
    && chown -R www-data:www-data storage bootstrap/cache /data/caddy /config/caddy

USER www-data
EXPOSE 8000
