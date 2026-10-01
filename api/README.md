# api

Laravel 13 (PHP 8.4): public API `/api/v1`, Filament admin at `/admin`, queues via Horizon.

Runs in Docker, see the root README. Common commands from the repo root:

```bash
docker compose -f infra/docker-compose.yml exec api php artisan migrate
docker compose -f infra/docker-compose.yml exec api php artisan make:filament-user
docker compose -f infra/docker-compose.yml exec api ./vendor/bin/pest
docker compose -f infra/docker-compose.yml exec api ./vendor/bin/pint
```
