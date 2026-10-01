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

Демо-данные в базе (тот же каталог, что на сайте; админ `admin@example.com` / `password`):

```bash
docker compose -f infra/docker-compose.yml exec api php artisan migrate:fresh --seed
```

Тесты и стиль:

```bash
docker compose -f infra/docker-compose.yml exec api ./vendor/bin/pest
docker compose -f infra/docker-compose.yml exec api ./vendor/bin/pint
```

`api/vendor` и `web/node_modules` внутри контейнеров лежат в Docker-томах: так Laravel отвечает за 0,2 с вместо 5–10 с
на Windows. После изменения `composer.json` выполните `composer install` внутри контейнера `api`.

Сайт в контейнере работает на webpack с опросом файлов (события файлов не проходят через монтирование с Windows).
Для самой быстрой перезагрузки при работе над вёрсткой запускайте сайт на хосте, а в Docker держите api и базу:

```bash
cd web
cp .env.example .env.local
npm install
npm run dev
```

## Демо-контент

Пока нет API каталога (шаг 4.5), сайт работает на демо-данных: `web/src/data/demo/` и фото в `web/public/demo/`.
Фирмы, отзывы, рейтинги, цены и даты там вымышлены, сверху сайта висит плашка «sample data»
(`NEXT_PUBLIC_DEMO_CONTENT=false` её убирает). Все страницы читают данные только через `web/src/lib/catalog.ts`:
при переходе на API меняются функции в этом файле, а не страницы. Сидер Laravel читает тот же каталог из
`api/database/seeders/data/demo-catalog.json` (экспорт из `web/src/data/demo`). Перед запуском демо-фото заменяются реальными
фото фирм с письменным разрешением.

## Правила работы

- Один этап плана = одна ветка и один PR в `main`.
- CI (`.github/workflows/ci.yml`) должен быть зелёным перед мержем.
