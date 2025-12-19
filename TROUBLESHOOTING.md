# 🔧 Troubleshooting - Решение проблем

## Проблема: Cargo error "no matching package found: tower-governor"

### Симптомы:
```
error: no matching package found
searched package name: tower-governor
perhaps you meant:      tower_governor
```

### Решение:
Эта проблема уже исправлена в commit `d131d6e`. Обновите код:

```powershell
git pull origin master
docker-compose up -d --build
```

---

## Проблема: Docker build error "Cargo.lock not found"

### Симптомы:
```
ERROR [rust_iss build 4/7] COPY Cargo.toml Cargo.lock ./
failed to solve: failed to compute cache key: "/Cargo.lock": not found
```

### Решение:
Эта проблема уже исправлена в commit `9c8da04`. Обновите код:

```powershell
git pull origin master
docker-compose up -d --build
```

---

## Проблема: Docker Hub timeout (TLS handshake)

### Симптомы:
```
failed to solve: failed to fetch oauth token: Post "https://auth.docker.io/token": 
net/http: TLS handshake timeout
```

### Решение 1: Использовать Docker BuildKit с кэшированием

```powershell
# Включить BuildKit
$env:DOCKER_BUILDKIT=1
$env:COMPOSE_DOCKER_CLI_BUILD=1

# Попробовать снова
docker-compose build --no-cache rust_iss
```

### Решение 2: Использовать зеркало Docker Hub

Добавьте в `C:\Users\<USER>\.docker\daemon.json`:

```json
{
  "registry-mirrors": ["https://mirror.gcr.io"]
}
```

Перезапустите Docker Desktop.

### Решение 3: Подождать и повторить попытку

Часто это временная проблема сети. Подождите 5-10 минут и попробуйте снова.

---

## Проблема: Порт 8080 уже занят

### Симптомы:
```
Error: bind: address already in use
```

### Решение:

```powershell
# Найти процесс, занимающий порт 8080
netstat -ano | findstr :8080

# Остановить процесс (замените <PID> на реальный ID процесса)
Stop-Process -Id <PID> -Force

# Или изменить порт в docker-compose.yml:
# services -> web_nginx -> ports: "8081:80"
```

---

## Проблема: Дубликаты в OSDR таблице

### Симптомы:
В базе данных появляются дубликаты записей OSDR.

### Решение:

```powershell
# Запустить скрипт очистки дубликатов
.\fix-osdr-dedupe.ps1

# Проверить результат
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*), COUNT(DISTINCT dataset_id) FROM osdr_items;"
```

Должно быть 0 дубликатов.

---

## Проблема: Redis "Class not found"

### Симптомы:
```
Class "Redis" not found in CacheApiResponse.php
```

### Решение:
Redis middleware временно отключено в routes/web.php. 

Для установки Redis extension в PHP:

```bash
# Внутри php контейнера
docker exec -it php_web bash
pecl install redis
docker-php-ext-enable redis
exit

# Перезапустить контейнер
docker-compose restart php_web
```

Затем раскомментировать CacheApiResponse в `routes/web.php`.

---

## Проблема: PostgreSQL "connection refused"

### Симптомы:
```
could not connect to server: Connection refused
```

### Решение:

```powershell
# 1. Проверить, запущен ли контейнер БД
docker-compose ps

# 2. Проверить логи
docker-compose logs db

# 3. Перезапустить БД
docker-compose restart db

# 4. Если не помогает - пересоздать
docker-compose down
docker volume rm he-path-of-the-samurai_pg_data
docker-compose up -d
```

**⚠️ Внимание**: Последняя команда удалит все данные из БД!

---

## Проблема: Laravel 500 error

### Симптомы:
Белый экран или 500 Internal Server Error на страницах Laravel.

### Решение:

```powershell
# Проверить логи PHP
docker-compose logs php_web

# Проверить логи Nginx
docker-compose logs web_nginx

# Зайти в контейнер и посмотреть Laravel логи
docker exec -it php_web bash
tail -f storage/logs/laravel.log
```

Чаще всего проблема в:
- Отсутствии прав на storage/logs
- Неправильных credentials БД
- Отсутствующих миграциях

---

## Полезные команды для диагностики

```powershell
# Статус всех контейнеров
docker-compose ps

# Логи всех сервисов
docker-compose logs -f

# Логи конкретного сервиса
docker-compose logs -f rust_iss

# Рестарт всех сервисов
docker-compose restart

# Остановка и удаление контейнеров
docker-compose down

# Пересборка с нуля
docker-compose down
docker-compose build --no-cache
docker-compose up -d

# Проверка состояния БД
docker exec iss_db psql -U monouser -d monolith -c "\dt"

# Проверка количества записей OSDR
docker exec iss_db psql -U monouser -d monolith -c "SELECT COUNT(*) FROM osdr_items;"
```

---

## Контакты для поддержки

Если проблема не решена, создайте Issue на GitHub:
https://github.com/Toxonicon/he-path-of-the-samurai/issues
