FROM dunglas/frankenphp:latest AS base

RUN apt-get update && apt-get install --no-install-recommends -y unzip \
    && rm -rf /var/lib/apt/lists/* \
    && install-php-extensions apcu pdo_pgsql intl zip gd pcntl \
    && echo "apc.enable_cli=1" > /usr/local/etc/php/conf.d/apcu-cli.ini \
    && printf 'upload_max_filesize=20M\npost_max_size=24M\n' > /usr/local/etc/php/conf.d/uploads.ini \
    # Smalot\PdfParser is memory-hungry on larger PDFs (parsing an ~8MB manual exhausted
    # the 128M default and killed the queue worker process, not just the job).
    && printf 'memory_limit=512M\n' > /usr/local/etc/php/conf.d/memory.ini

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

RUN php artisan filament:assets
