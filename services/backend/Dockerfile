FROM dunglas/frankenphp:latest AS base

RUN apt-get update && apt-get install --no-install-recommends -y unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions apcu \
    && echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/apcu-cli.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

FROM base AS dev

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-progress

COPY . .

COPY docker/dev-entrypoint.sh /usr/local/bin/dev-entrypoint.sh
RUN chmod +x /usr/local/bin/dev-entrypoint.sh

ENTRYPOINT ["/usr/local/bin/dev-entrypoint.sh"]

FROM base AS prod

COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-scripts --no-progress --no-dev --optimize-autoloader

COPY . .
