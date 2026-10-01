# go-kyrgyzstan (Tunduk Trips): технический план реализации

## Контекст

Документ «Запуск go-kyrgyzstan» (версия 1 от 30.09.2026) описывает англоязычный маркетплейс туров по Кыргызстану.
Турист отправляет заявку, вы подтверждаете места у турфирмы, турист платит предоплату (≈15% = комиссия площадки),
остаток платит фирме на месте. Вехи: **1 февраля 2027** каталог открыт для индексации (оплата вручную),
**1 марта 2027** полный запуск (онлайн-предоплата, ИИ-ассистент, реклама).

Стек зафиксирован в документе: монорепо, `api/` = Laravel + Filament (PHP 8.4), `web/` = Next.js App Router + TS +
Tailwind + shadcn/ui, PostgreSQL 17, Redis 7, Docker Compose на VPS Hetzner, Caddy, Cloudflare (DNS, CDN, R2).

Этот файл переводит документ в порядок работы для разработки: что строим, в какой последовательности,
какие решения принимаем заранее и что я добавляю к исходному плану.

**Состояние машины (проверено 01.10.2026):** есть git 2.55, Node 26, npm 11, WSL2. Нет PHP, Composer, Docker, gh.
Папка `OpenClaw Test` пустая, не git-репозиторий. Репозиторий `github.com/moldakunoff-cpu/go-kyrgyzstan` недоступен
(приватный или ещё не создан).

**Решения, принятые 01.10.2026:**
- Код берём клонированием существующего репозитория `moldakunoff-cpu/go-kyrgyzstan` с GitHub (нужны gh и доступ).
- Окружение: Docker Desktop + WSL2, весь PHP в контейнерах.
- Первый PR: каркас монорепо (шаги 3.1–3.5).
- Бренд в коде: **Tunduk Trips**, префикс брони **TT-** (`TT-27-0142`). Оба значения хранятся в конфиге.

---

## 1. Что строим (сжатая спецификация из документа)

### Страницы `web/` (карта сайта)
| Маршрут | Назначение | Индекс |
|---|---|---|
| `/` | Поиск (Activity · When · Duration), полоса доверия, подборки, карта регионов, How booking works | да |
| `/tours` | Каталог, фильтры в URL (nuqs): activity, region, month, duration, price, difficulty, type, sort | да (пустые выдачи noindex) |
| `/tours/[slug]` | Галерея, факты, программа по дням + карта маршрута, включено/нет, блок фирмы, отзывы, FAQ, липкий виджет брони | да |
| `/destinations/[region]`, `/destinations/[region]/[activity]`, `/activities/[activity]` | SEO-посадочные | да |
| `/collections/[slug]`, `/operators/[slug]`, `/guides/[slug]` | Подборки, профиль фирмы, гайды с карточками туров | да |
| `/plan-my-trip` | Квиз из 8 шагов → inquiry tailor_made | да |
| `/favorites` | Избранное в localStorage | noindex |
| `/booking/[token]` | «Моя бронь»: статус, оплата, ваучер, отмена, отзыв | noindex |
| `/about`, `/how-it-works`, `/booking-protection`, `/terms`, `/privacy`, `/cancellation-policy`, `/contact` | Доверие и юридические страницы | да |

### Бронь: статусы и переходы
`new → checking → awaiting_payment (48 ч) → deposit_paid → voucher_sent → completed`,
боковые: `declined`, `expired`, `cancelled_by_tourist` (возврат 100/50/0% при 30+/14–29/<14 дней), `cancelled_by_operator` (100%).
Каждый переход пишется в `booking_events`. Автопереходы (таймер, вебхук) делает система.

### Публичное API `/api/v1`
GET `tours`, `tours/{slug}`, `tours/{slug}/availability`, `regions`, `activities`, `collections`, `operators/{slug}`,
`posts/{slug}`, `currency-rates`; POST `bookings`, GET `bookings/{token}`, POST `bookings/{token}/checkout|cancel|review`,
POST `inquiries`, POST `assistant/messages` (SSE), POST `webhooks/payments/{provider}`.

### Модель данных (из документа)
operators, tours, tour_days, departures, private_prices, tour_items, regions, activities (+ pivot), collections (+ pivot),
bookings, booking_events, payments, refunds, webhook_events, inquiries, offers, reviews, posts (+ post_tour),
currency_rates, users (admin | manager | content, telegram_chat_id). Деньги в центах (int), валюта USD.

---

## 2. Мои рекомендации (дополнения к документу)

**Модель данных: недостающее**
1. **`tour_faqs`** (tour_id, question, answer, sort). FAQ есть на странице тура и в JSON-LD, но таблицы в схеме нет.
2. **`operator_guides`** (operator_id, name, photo, languages[], bio). Блок фирмы показывает гидов с фото и языками.
3. **Цена для детей.** В брони есть `children`, но цены для детей нет нигде. Нужно поле `child_price_cents` (null = как взрослый)
   в departures и private_prices, плюс `min_age` у тура.
4. **Участники брони.** Лист брони для фирмы содержит имена всех туристов: `booking_travelers` (или JSON `travelers`).
5. **Согласие с условиями.** `terms_accepted_at`, `terms_version`, IP. Это защита от чарджбэков (риск из раздела 15).
6. **Снимок цены в брони.** Фиксировать `unit_price_cents`, `pricing_source` (departure | private | offer) и `commission_rate`
   на момент заявки, чтобы правка тура в админке не меняла существующие брони.
7. **Мест в заезде.** `seats_booked` увеличивать при переходе в `awaiting_payment` (места держатся 48 ч), уменьшать при
   `expired`/отмене. Делать это в транзакции с `lockForUpdate`. Бейдж «Only N spots left» считать от этого поля.

**Безопасность и деньги**
8. **Токен брони храним хешем.** В письме полный секрет (32+ символа, `Str::random`), в базе `sha256`. ULID из документа
   на 48 бит предсказуем по времени, как секрет его не используем. Отдельно `code` (GK-27-0142) для людей.
9. **Контакты фирмы не уходят в публичное API.** API Resources только по белому списку полей; phone/whatsapp/email
   фирмы выдаются только в ваучере после `deposit_paid`. На это нужен отдельный тест.
10. **Часовой пояс.** Даты туров хранить как `date` (без времени), расчёт возврата в `Asia/Bishkek`. Иначе турист из США
    на границе 30 дней получит не тот процент.
11. **`ManualGateway` с первого дня.** До 1 марта оплата идёт по ссылке, выставленной вручную. Это отдельный драйвер
    `PaymentGateway`: админ вставляет ссылку и отмечает оплату. Поток статусов тогда работает с февраля, а боевой провайдер
    подключается заменой драйвера.

**Архитектура web ↔ api**
12. **BFF через Next.js.** Клиентские вызовы (форма брони, квиз, ассистент) идут в route handlers Next.js, а те
    проксируют в `api` по внутренней сети Docker. Это даёт один origin без CORS и прячет `api` от прямого трафика.
    IP туриста передаётся в `X-Forwarded-For` (доверенный прокси в Laravel), иначе rate limit сработает на весь сайт.
13. **Сброс кэша по требованию вместо 5-минутного TTL.** Observer тура в Laravel вызывает `POST /api/revalidate` в Next.js
    (`revalidateTag('tour:slug')`, `revalidateTag('catalog')`). Правка видна на сайте сразу, а страницы при этом статичные.
14. **Картинки.** Medialibrary уже делает WebP 480/960/1600. В `next/image` подключить свой loader, который выбирает
    конверсию с `cdn.`, и не гонять оптимизацию через CPU VPS.
15. **Готовность к i18n.** DE/FR запланированы на этап 2. Сейчас сайт только на английском, но строки интерфейса выносим
    в словарь (`next-intl`), а у переводимых полей в базе оставляем место (`spatie/laravel-translatable` или JSON-поля).
    Сейчас это стоит дня работы, а через год обойдётся в переписывание.
16. **Фильтр по месяцу.** Тур подходит месяцу, если у него есть заезд в этом месяце или `has_private_option` и месяц
    входит в сезон (`season_from`/`season_to` нужен и на уровне тура, не только у private_prices).

**Процесс**
17. **Разработка на Windows.** Репозиторий держим внутри файловой системы WSL2 (`~/code/go-kyrgyzstan`), PHP и Composer
    запускаем в контейнерах. Bind-mount с диска C: из Docker делает Laravel в 5–10 раз медленнее.
18. **Тесты.** Pest (совместим с PHPUnit, короче). Обязательные тесты: DepositCalculator, RefundPolicy (границы 14/30 дней,
    часовой пояс), BookingTransitions (все разрешённые и запрещённые), идемпотентность вебхука, закрытость контактов фирмы.
19. **Префикс кода брони.** `GK-` идёт от go-kyrgyzstan; при бренде Tunduk Trips логичнее `TT-`. Делаем префикс настройкой.

---

## 3. Порядок разработки (этапы = ветки = PR)

Нумерация шагов как в документе. Нетехнические шаги (домен, юрист, эквайринг, фирмы, логотип) идут параллельно и на вас.

| # | Этап | Шаги документа | Результат | Срок |
|---|---|---|---|---|
| 0 | Окружение | — | Docker Desktop + WSL2, репозиторий, gh | 1 день |
| 1 | Каркас монорепо | 3.1–3.5 | api (Laravel + Filament), web (Next.js), infra compose (pg17, redis7, mailpit), CI | 2–3 дня |
| 2 | Дизайн-токены и styleguide | 2.2 | Тема Tailwind, Unbounded + Onest, `/styleguide` | 1 день |
| 3 | Лендинг + юридические страницы | 1.4 | «Coming spring 2027» + Terms/Privacy/Cancellation/Contact для заявки в банк | 1–2 дня |
| 4 | Данные | 4.1 + рекомендации 1–7 | Миграции, модели, фабрики, сидер 3 фирмы / 12 туров | 2–3 дня |
| 5 | Админка каталога | 4.2–4.4 | Filament: фирмы, туры (вкладки), справочники, подборки, гайды, медиа | 4–5 дней |
| 6 | Публичное API | 4.5 | GET-эндпоинты, фильтры, кэш, OpenAPI (Scramble) | 3 дня |
| 7 | Фронт каталога | 5.1–5.5, 5.9–5.11 | Layout, главная, каталог, тур, посадочные, гайды, валюта | 2–3 недели |
| 8 | Бронь | 4.6–4.9, 5.6–5.7 | Заявка, статусы, письма, Telegram, планировщик, «Моя бронь», ManualGateway | 1,5 недели |
| 9 | Квиз и отзывы | 4.10–4.11, 5.8 | Plan my trip → offer → бронь; отзывы | 1 неделя |
| 10 | Прод | 3.6–3.9 | VPS, деплой из GHCR, бэкапы, мониторинг | 2 дня |
| 11 | SEO и аналитика | 7.1–7.3 | sitemap, JSON-LD, OG, GA4/GTM, Consent Mode v2 | 3 дня |
| — | **1 февраля: индексация** | 7.6 | | |
| 12 | Онлайн-оплата | 6.1–6.5 | Драйвер провайдера, вебхук, возвраты | 1 неделя |
| 13 | ИИ-ассистент | 8.3 | SSE-эндпоинт, 4 инструмента, виджет | 4 дня |
| 14 | E2E и запуск | 9.1–9.5 | Playwright критического пути в CI | 2 дня |
| — | **1 марта: запуск** | | | |

Этап 3 (лендинг) я поднял выше, чем в документе: банку для эквайринга нужен живой сайт с юридическими страницами,
а эквайринг занимает 1–3 недели. Лендинг можно выложить на Vercel или Cloudflare Pages ещё до VPS.

---

## 4. Первый шаг: что делаем сразу после утверждения плана (этапы 0–1)

**Этап 0: окружение (делаете вы, я проверяю)**
- Установить Docker Desktop (WSL2 backend, интеграция с дистрибутивом Ubuntu), перезагрузка.
- `winget install GitHub.cli`, затем `gh auth login`. Если репозиторий ещё не создан: `gh repo create moldakunoff-cpu/go-kyrgyzstan --private`.
- Я клонирую репозиторий в WSL2 (`~/code/go-kyrgyzstan`) и открываю его в VS Code через Remote-WSL.
  Если работать нужно именно с диска C:, клонирую в `C:\Users\Aman Zhanserkeev\OpenClaw Test\go-kyrgyzstan`.
  Это работает, но Laravel будет медленнее.
- Проверка: `docker compose version`, `gh repo view moldakunoff-cpu/go-kyrgyzstan`.

**Этап 1: каркас (ветка `chore/scaffold`)**
```
go-kyrgyzstan/
├── api/                 # Laravel 12+, PHP 8.4, Filament, Horizon, Scramble, spatie/*
├── web/                 # Next.js (App Router, TS, Tailwind, shadcn/ui, nuqs, zod, react-hook-form, maplibre-gl)
├── infra/
│   ├── docker-compose.yml        # pgsql17, redis7, mailpit, api (php 8.4-fpm/artisan serve), web (node)
│   ├── docker/api.Dockerfile, docker/web.Dockerfile
│   └── Caddyfile                 # на будущее (прод)
├── .github/workflows/ci.yml      # api: pint + pest на postgres service; web: eslint, tsc --noEmit, next build
├── .editorconfig, .gitignore, README.md (команды запуска)
```
- Laravel создаём контейнером: `docker run --rm -v ...:/app composer create-project laravel/laravel api`.
- `api`: `install:api`, Filament panel на `/admin`, пакеты из раздела 07, `/api/v1/health`, Pest.
- `web`: `create-next-app`, `shadcn init`, страница-заглушка, `API_URL` в `.env.example`.
- `config/brand.php` (`name` = Tunduk Trips, `booking_prefix` = TT) и `NEXT_PUBLIC_BRAND_NAME` в web.
- Готово, когда `docker compose up` поднимает сайт на `localhost:3000`, админку на `localhost:8000/admin`,
  почту на `localhost:8025`, а CI в первом PR зелёный. Затем коммит в ветку `chore/scaffold`, push, PR через `gh pr create`.

---

## 5. Проверка (как убеждаемся, что работает)

- **Каждый PR:** CI зелёный (pint, pest, eslint, tsc, next build).
- **Каркас:** `docker compose up`; открыть 3000, 8000/admin, 8025; `curl localhost:8000/api/v1/health`.
- **Данные:** `php artisan migrate:fresh --seed` создаёт демо-каталог; Pest-тесты связей.
- **API:** feature-тесты фильтров; ответ тура не содержит контактов фирмы.
- **Бронь:** тесты переходов, расчёта предоплаты и возврата на границах 13/14/29/30 дней, повторного вебхука.
- **Фронт:** Lighthouse mobile 90+ (главная, каталог, тур); Rich Results Test для тура и статьи; проверка в браузере
  на ширине телефона.
- **Перед запуском:** Playwright e2e каталог → заявка → подтверждение → оплата (Fake/Manual) → ваучер → отмена.
