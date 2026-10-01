#!/bin/sh
# Dev entrypoint: makes a fresh clone runnable with a single `docker compose up`.
set -e

[ -f vendor/autoload.php ] || composer install --no-interaction
if [ ! -f .env ]; then
  cp .env.example .env
  php artisan key:generate --no-interaction
fi
php artisan migrate --force --no-interaction

exec "$@"
