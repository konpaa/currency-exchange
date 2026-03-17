# Currency Exchange

Система конвертации валют на Laravel.

- Точная арифметика (bcmath, без float)
- Курсы из [freecurrencyapi.com](https://freecurrencyapi.com) (интеграция через Guzzle/Laravel HTTP Client)
- Атомарное обновление курсов через поколения (без простоя)
- Кэширование в Redis (список валют, сессии)
- Админ-панель (Filament) — управление валютами, просмотр курсов, ручная синхронизация
- Автообновление курсов раз в сутки (02:00, контейнер `scheduler`)
- Сервис конвертации: `$converter->convert(123, 'USD', 'RUB')`

## Архитектура

```
app/
├── Contracts/              # Интерфейсы (CurrencyRateProvider, CurrencyRateRepository)
├── Repositories/           # DatabaseCurrencyRateRepository (поколения, покрывающий индекс)
├── Services/Currency/
│   ├── FreeCurrencyApiProvider.php   # HTTP-интеграция с freecurrencyapi.com
│   ├── CurrencyConversionService.php # Конвертация (bcmath)
│   └── CurrencySyncService.php       # Синхронизация курсов
├── Models/                 # Currency, CurrencyRate
├── Filament/Resources/     # Админка: валюты + курсы
└── Http/
    ├── Requests/            # ConvertCurrencyRequest (валидация по списку валют)
    └── Controllers/         # CurrencyConversionController
```

## Настройка

1. Скопировать `.env.example` в `.env`, заполнить ключи:

| Переменная | Описание | По умолчанию |
|---|---|---|
| `FREE_CURRENCY_API_KEY` | API-ключ с freecurrencyapi.com | — |
| `CURRENCY_BASE` | Базовая валюта для курсов | `USD` |
| `CACHE_STORE` | Драйвер кэша | `redis` |
| `REDIS_HOST` | Хост Redis | `redis` |
| `DB_*` | Параметры MySQL | — |

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
| `mysql` | MySQL 8.4 |
| `redis` | Redis 7 (кэш, сессии) |

## API

| Метод | URL | Описание |
|---|---|---|
| `GET` | `/api/currencies` | Список доступных валют |
| `GET` | `/api/convert?amount=100&from=USD&to=EUR` | Конвертация (результат с 2 знаками, округление) |

## Админ-панель

`/admin` — два раздела:
- **Валюты** — CRUD, включение/отключение, синхронизация курсов (по одной, выбранным или всем)
- **Курсы валют** — read-only таблица всех сохранённых курсов текущего поколения

## Команды

```bash
docker compose exec app php artisan currency:update-rates   # обновить курсы вручную
docker compose exec app php artisan test                     # тесты (БД testing)
```

Или через Makefile: `make migrate`, `make test`, `make rates-update`.

## Тесты

Тесты работают с тестовой БД MySQL (`testing`). Покрытие:
- Unit: конвертация (bcmath, ошибки, точность), репозиторий (CRUD, атомарность)
- Feature: API конвертации (валидация, переводы ошибок), команда обновления курсов
