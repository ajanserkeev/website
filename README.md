# Tunduk Trips

Маркетплейс туров по Кыргызстану для иностранных путешественников. Рабочее имя проекта: go-kyrgyzstan.
План реализации и решения: [docs/PLAN.md](docs/PLAN.md).

## Структура

| Папка | Что внутри |
|---|---|
| `web/` | Сайт для туристов: Next.js (App Router), TypeScript, Tailwind CSS, shadcn/ui |
| `api/` | Laravel 13 (PHP 8.4): API v1, админка Filament, очереди Horizon |
| `infra/` | Docker Compose для локальной разработки, Dockerfile-ы |
| `docs/` | План и документация |

## Запуск локально

Нужны Docker Desktop (WSL2) и Node.js 20.9+.

```bash
docker compose -f infra/docker-compose.yml up
```

| Адрес | Сервис |
|---|---|
| http://localhost:3000 | сайт (web) |
| http://localhost:8000/admin | админка (api) |
| http://localhost:8025 | Mailpit, входящие письма |
| http://localhost:8000/api/v1/health | проверка API и базы |
| localhost:5432 | PostgreSQL 17 (tunduk / secret), базы `tunduk` и `tunduk_test` |

Первый запуск сам ставит зависимости, создаёт `api/.env` и прогоняет миграции. Пользователь для админки:

```bash
docker compose -f infra/docker-compose.yml exec api php artisan make:filament-user
```

Тесты и стиль:

```bash
docker compose -f infra/docker-compose.yml exec api ./vendor/bin/pest
docker compose -f infra/docker-compose.yml exec api ./vendor/bin/pint
```

`api/vendor` и `web/node_modules` внутри контейнеров лежат в Docker-томах: так Laravel отвечает за 0,2 с вместо 5–10 с
на Windows. После изменения `composer.json` выполните `composer install` внутри контейнера `api`.

Только сайт, без Docker:

```bash
cd web
cp .env.example .env.local
npm install
npm run dev
```

## Правила работы

- Один этап плана = одна ветка и один PR в `main`.
- CI (`.github/workflows/ci.yml`) должен быть зелёным перед мержем.
