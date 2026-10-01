# Tunduk Trips

Маркетплейс туров по Кыргызстану для иностранных путешественников. Рабочее имя проекта: go-kyrgyzstan.
План реализации и решения: [docs/PLAN.md](docs/PLAN.md).

## Структура

| Папка | Что внутри |
|---|---|
| `web/` | Сайт для туристов: Next.js (App Router), TypeScript, Tailwind CSS, shadcn/ui |
| `api/` | Laravel: API v1, админка Filament, очереди *(появится в следующем шаге каркаса)* |
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
| localhost:5432 | PostgreSQL 17 (tunduk / secret) |

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
