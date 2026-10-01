# PHP image for the Laravel app in api/.
# `dev` is used by infra/docker-compose.yml; the production target is added in step 3.7.
FROM php:8.4-cli-bookworm AS base

COPY --from=mlocati/php-extension-installer:2 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql redis intl zip gd exif pcntl bcmath opcache \
 && apt-get update && apt-get install -y --no-install-recommends git unzip postgresql-client \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS dev
ENV COMPOSER_CACHE_DIR=/tmp/composer-cache
COPY docker/php-dev.ini /usr/local/etc/php/conf.d/zz-dev.ini
COPY docker/api-dev-entrypoint.sh /usr/local/bin/api-dev-entrypoint
RUN chmod +x /usr/local/bin/api-dev-entrypoint
EXPOSE 8000
ENTRYPOINT ["api-dev-entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
