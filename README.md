# Currency Exchange

Система конвертации валют на Laravel.

- Точная арифметика (bcmath, без float)
- Курсы из [freecurrencyapi.com](https://freecurrencyapi.com)
- Атомарное обновление курсов (через поколения, без простоя)
- Админ-панель (Filament) для управления валютами и синхронизации курсов
- Автообновление курсов раз в сутки (02:00, контейнер `scheduler`)

## Настройка

1. Скопировать `.env.example` в `.env`, заполнить ключи:

| Переменная | Описание |
|---|---|
| `FREE_CURRENCY_API_KEY` | API-ключ с freecurrencyapi.com |
| `CURRENCY_BASE` | Базовая валюта (по умолчанию `USD`) |
| `DB_*` | Параметры MySQL |

2. Запуск:

```bash
docker compose up -d
docker compose exec app php artisan migrate --force
docker compose exec app php artisan db:seed
```

3. Создать админа: `docker compose exec app php artisan make:filament-user`

## Docker-контейнеры

| Контейнер | Назначение |
|---|---|
| `app` | Веб-приложение (порт 80) |
| `scheduler` | Планировщик Laravel (`schedule:work`). Курсы обновляются ежедневно в 02:00 |
| `mysql` | БД MySQL 8.4 |

## API

| Метод | URL | Описание |
|---|---|---|
| `GET` | `/api/currencies` | Список доступных валют |
| `GET` | `/api/convert?amount=100&from=USD&to=EUR` | Конвертация суммы |

## Админ-панель

`/admin` — управление валютами, ручная синхронизация курсов.

## Команды

```bash
docker compose exec app php artisan currency:update-rates   # обновить курсы вручную
docker compose exec app php artisan test                     # тесты (БД testing)
```

Или через Makefile: `make migrate`, `make test`, `make rates-update`.
