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
| `worker` | Horizon: фото в WebP, письма туристам, Telegram |
| `scheduler` | таймеры броней каждые 15 минут: истечение ссылки, напоминания, просьба об отзыве |
| localhost:5432 | PostgreSQL 17 (tunduk / secret), базы `tunduk` и `tunduk_test` |

Первый запуск сам ставит зависимости, создаёт `api/.env` и прогоняет миграции. Пользователь для админки:

```bash
docker compose -f infra/docker-compose.yml exec api php artisan make:filament-user
```

Демо-данные в базе (тот же каталог, что на сайте, с фото; админ `admin@example.com` / `password`):

```bash
docker compose -f infra/docker-compose.yml exec api php artisan migrate:fresh --seed
```

Тесты работают только с базой `tunduk_test` и откажутся запускаться на любой другой.

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

## Как работает бронь

1. Турист на странице тура жмёт «Check availability» → форма в 3 шага → `POST /api/bookings` (Next.js) → Laravel.
2. В админке «Брони»: «Взять в работу» → «Фирма подтвердила» (вставить ссылку на оплату из кабинета эквайера) →
   «Оплата получена» (турист получает ваучер с контактами фирмы). Кнопка «Написать фирме» открывает WhatsApp с готовым текстом.
3. Турист видит всё на странице «Моя бронь» по секретной ссылке из письма; там же отмена с расчётом возврата.
4. Письма в разработке приходят в Mailpit (http://localhost:8025). Telegram включается `TELEGRAM_BOT_TOKEN` и chat id у пользователя.

## Карты

Карты перенесены из прототипа коллеги [GO-Kyrgyzstan](https://github.com/kgtulpar-ui/GO-Kyrgyzstan) (ветка `development`)
и встроены в наш стек:

- **Сайт:** `/map` — места и маршруты всех туров, фильтры по типам, карточки мест и туров; блок карты на главной;
  на странице тура «Route map» с отрезками по способу передвижения, номерами дней и профилем высот.
  MapLibre GL, подложки OpenFreeMap (вектор) и Esri (спутник), рельеф и 3D из AWS Terrain Tiles.
- **Админка:**
  - «Места на карте» (точка ставится кликом, высота подтягивается сама);
  - в туре у каждого дня поле «Место дня на карте»;
  - вкладка «Маршрут»:
    - точки на карте;
    - режим каждого отрезка (авто / пешком / верхом / по прямой);
    - «Собрать из дней программы»;
    - загрузка GPX;
    - профиль высот.
- **Расчёт** (`api/app/Services/Maps/RouteBuilder.php`): дороги через OSRM, тропы через пеший OSRM FOSSGIS, высоты Open-Meteo.
  Где роутер не нашёл разумного пути, отрезок рисуется прямой с пометкой «приблизительно». Настройки: `OSRM_*`, `ELEVATION_API_URL`.
- Демо-маршруты посчитаны заранее и лежат в `demo-catalog.json` (`routeGeojson`), чтобы сидер работал без сети.
  После правки `routePlan` или мест: `docker compose -f infra/docker-compose.yml exec api php artisan demo:build-routes`.

## Данные и демо-контент

Сайт берёт всё из API Laravel (`/api/v1`): туры, регионы, подборки, гайды, отзывы, курсы валют. Цена «от», предоплата,
бейджи и рейтинг считаются на сервере (`api/app/Services/Catalog`). Данные на сайте кэшируются на 5 минут и
сбрасываются сразу после правки в админке (`RevalidateFrontend` → `POST /api/revalidate`).

Пока в базе демо-каталог из сидера (`api/database/seeders/data/demo-catalog.json`, фото из `web/public/demo`):
фирмы, отзывы, рейтинги, цены и даты вымышлены. Плашку «sample data» убирает `NEXT_PUBLIC_DEMO_CONTENT=false`.
Перед запуском демо-туры удаляются, а фото заменяются реальными с письменным разрешением фирм.

## Правила работы

- Один этап плана = одна ветка и один PR в `main`.
- CI (`.github/workflows/ci.yml`) должен быть зелёным перед мержем.
