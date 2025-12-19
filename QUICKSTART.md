# 🚀 БЫСТРЫЙ СТАРТ - Кассиопея Space Monitor

## 🆕 Запуск на новом устройстве (первый раз)

### Предварительные требования:
- Docker Desktop установлен и запущен
- Git установлен

### Шаги:

```powershell
# 1. Клонировать репозиторий
git clone https://github.com/Toxonicon/he-path-of-the-samurai.git
cd he-path-of-the-samurai

# 2. Скопировать .env файл
Copy-Item .env.example .env -ErrorAction SilentlyContinue

# 3. Запустить проект (сборка может занять 5-10 минут при первом запуске)
docker-compose up -d --build

# 4. Дождаться готовности (проверить логи)
docker-compose logs -f rust_iss

# 5. Открыть веб-интерфейс
Start-Process "http://localhost:8080/dashboard"
```

**Готово!** 🎉 Система запущена.

---

## ⚡ Быстрый запуск (если проект уже клонирован)

```powershell
# Запуск всех сервисов
docker-compose up -d

# Проверка статуса
docker-compose ps

# Открыть веб-интерфейс
Start-Process "http://localhost:8080/dashboard"
```

**Готово!** 🎉 Система запущена.

---

## 📝 Важная информация

### Текущая конфигурация:
- ✅ **PostgreSQL 16** - База данных (порт 5432)
- ✅ **Redis 7** - Кэш (порт 6379)
- ✅ **Rust ISS API** - Backend (порт 8081)
- ✅ **PHP 8.3 + Laravel 11** - Frontend
- ✅ **Nginx** - Web-сервер (порт 8080)
- ⚠️ **rust_legacy** - Временно отключен (проблемы с Docker Hub)

### AstronomyAPI:
В Laravel уже настроены **demo credentials** для AstronomyAPI:
- На странице http://localhost:8080/astronomy показываются примеры событий
- Для реальных данных см. `ASTRONOMY_QUICKSTART.md`

---

## Автоматический запуск (альтернативный метод)

### Windows PowerShell:
```powershell
# 1. Переименовать новый main.rs
Move-Item services\rust-iss\src\main.rs services\rust-iss\src\main_old.rs -Force
Move-Item services\rust-iss\src\main_new.rs services\rust-iss\src\main.rs -Force

# 2. Создать Cargo.lock для rust-legacy
Set-Location services\rust-legacy
cargo generate-lockfile
Set-Location ..\..

# 3. Сборка и запуск
docker-compose build --no-cache
docker-compose up -d

# 4. Проверка статуса
docker-compose ps
```

### Linux/Mac:
```bash
# 1. Переименовать новый main.rs
mv services/rust-iss/src/main.rs services/rust-iss/src/main_old.rs
mv services/rust-iss/src/main_new.rs services/rust-iss/src/main.rs

# 2. Создать Cargo.lock
cd services/rust-legacy && cargo generate-lockfile && cd ../..

# 3. Сборка и запуск
docker-compose build --no-cache
docker-compose up -d

# 4. Проверка
docker-compose ps
```

## Проверка работоспособности

```powershell
# Health check Rust API
curl http://localhost:8081/health

# Последняя позиция МКС
curl http://localhost:8081/last

# Web интерфейс
Start-Process "http://localhost:8080/dashboard"
```

## Просмотр логов

```powershell
# Все сервисы
docker-compose logs -f

# Конкретный сервис
docker-compose logs -f rust_iss
docker-compose logs -f rust_legacy
docker-compose logs -f php_web
```

## Остановка

```powershell
docker-compose down
# Или с удалением volumes:
docker-compose down -v
```

## Структура API endpoints

| Endpoint | Описание |
|----------|----------|
| `GET /health` | Health check |
| `GET /last` | Последняя позиция МКС |
| `GET /fetch` | Триггер обновления МКС |
| `GET /iss/trend` | Анализ движения МКС |
| `GET /osdr/sync` | Синхронизация OSDR |
| `GET /osdr/list` | Список OSDR datasets |
| `GET /space/{source}/latest` | Последние данные (apod/neo/flr/cme/spacex) |
| `GET /space/refresh?src=apod,neo` | Обновить источники |
| `GET /space/summary` | Сводка по всем источникам |

## Порты

- **8080** - Nginx (Web интерфейс)
- **8081** - Rust API
- **5432** - PostgreSQL
- **6379** - Redis

## Переменные окружения (.env)

Создайте файл `.env` в корне проекта:

```bash
# NASA API (получить на https://api.nasa.gov/)
NASA_API_KEY=DEMO_KEY

# Интервалы обновления (секунды)
FETCH_EVERY_SECONDS=600
ISS_EVERY_SECONDS=120
APOD_EVERY_SECONDS=43200
NEO_EVERY_SECONDS=7200
DONKI_EVERY_SECONDS=3600
SPACEX_EVERY_SECONDS=3600

# Rate limiting
RATE_LIMIT_PER_SEC=100

# Legacy генератор
LEGACY_PERIOD=300

# Логирование
RUST_LOG=info
```

## Troubleshooting

### Ошибка "main.rs not found"
Не забудьте переименовать `main_new.rs` → `main.rs`!

### Ошибка сборки Rust
Убедитесь, что создан `Cargo.lock`:
```powershell
cd services/rust-legacy
cargo generate-lockfile
```

### База данных не готова
Подождите 10-15 секунд для healthcheck:
```powershell
docker-compose logs db
```

### Порт занят
Измените порты в `docker-compose.yml`:
```yaml
ports:
  - "8081:3000"  # измените 8081 на свободный порт
```

## Документация

- Полный отчёт: `REFACTORING_REPORT.md`
- Архитектура: См. диаграммы в отчёте
- Код: `services/rust-iss/src/` и `services/rust-legacy/src/`
