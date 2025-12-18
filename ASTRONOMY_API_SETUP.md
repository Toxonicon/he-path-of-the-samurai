# Настройка AstronomyAPI

## Получение credentials

1. **Зарегистрируйтесь** на https://astronomyapi.com/auth/signup
2. **Войдите** в свой аккаунт
3. **Создайте приложение** на https://astronomyapi.com/dashboard
   - Нажмите "Create Application"
   - Укажите название приложения
   - В поле "Origin" укажите: `http://localhost:8080` (для локальной разработки)
4. **Сохраните credentials:**
   - `Application ID` - можно посмотреть в любое время
   - `Application Secret` - показывается только один раз! Сохраните его сразу

## Настройка проекта

1. Откройте файл `.env` в директории `services/php-web/laravel-patches/`

2. Добавьте следующие строки:
```env
ASTRO_APP_ID=ваш-application-id
ASTRO_APP_SECRET=ваш-application-secret
```

3. Перезапустите PHP контейнер:
```powershell
docker-compose restart php
```

## Проверка работы

1. Откройте в браузере: http://localhost:8080/astronomy
2. Вы должны увидеть астрономические события (восходы/заходы небесных тел)
3. Если видите "Demo data" - значит credentials не настроены

## API Endpoints

- **Bodies Positions**: `GET https://api.astronomyapi.com/api/v2/bodies/positions`
  - Получение позиций небесных тел
  - Параметры: `latitude`, `longitude`, `from_date`, `to_date`, `time`

- **Bodies**: `GET https://api.astronomyapi.com/api/v2/bodies`
  - Список всех небесных тел

## Лимиты бесплатного плана

- **500 запросов в месяц**
- Кеширование рекомендуется для экономии запросов

## Документация

Полная документация: https://docs.astronomyapi.com/

## Troubleshooting

### Ошибка 403 Forbidden
- Проверьте правильность `Application ID` и `Application Secret`
- Убедитесь что Origin в настройках приложения соответствует вашему домену

### Нет данных
- Проверьте что credentials добавлены в `.env`
- Убедитесь что PHP контейнер перезапущен после изменения `.env`
- Проверьте логи: `docker-compose logs php`
