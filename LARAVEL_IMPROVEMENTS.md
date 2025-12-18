# Laravel Frontend Improvements

## Обзор изменений

Проведён комплексный рефакторинг и улучшение frontend-части проекта Cassiopeia.

---

## 🏗️ Архитектурные улучшения

### Сервисный слой

Вынесена вся бизнес-логика из контроллеров в отдельные Service классы:

#### RustApiService (базовый)
- HTTP клиент для работы с Rust API
- Автоматическое кеширование ответов (5 минут)
- Централизованная обработка ошибок
- Логирование неудачных запросов

```php
protected function get(string $endpoint, array $params = [], bool $useCache = true): array
```

#### IssService
- `getLastPosition()` - текущая позиция МКС
- `getTrend($hours)` - тренд движения
- `getMetrics()` - метрики для дашборда
- `isVisible()` - проверка видимости

#### OsdrService  
- `getList($page, $perPage)` - список датасетов с пагинацией
- `getStats()` - статистика
- `filter($filters)` - фильтрация по типу/факторам/поиску
- `sort($items, $field, $direction)` - сортировка

#### JwstService
- `getImages($type, $page, $perPage)` - изображения по типу
- `getBySuffix($suffix)` - по суффиксу
- `getByProgram($programId)` - по программе
- `normalizeForGallery($items, $instrumentFilter)` - нормализация данных

### Dependency Injection

Все контроллеры используют DI через constructor injection:

```php
public function __construct(IssService $issService, JwstService $jwstService)
{
    $this->issService = $issService;
    $this->jwstService = $jwstService;
}
```

---

## 🎨 UI/UX улучшения

### animations.css

#### Анимации появления
- `fadeIn` - плавное появление с движением вверх
- `slideIn` - появление слева
- `pulse` - пульсация для loading
- `shimmer` - эффект загрузки

#### Skeleton loaders
```css
.skeleton {
    background: linear-gradient(90deg, #f0f0f0 0%, #f8f8f8 50%, #f0f0f0 100%);
    background-size: 200% 100%;
    animation: shimmer 1.5s infinite;
}
```

#### Hover эффекты
- Карточки поднимаются на 4px при наведении
- Увеличенная тень
- Плавные transitions (0.3s cubic-bezier)

#### JWST Gallery
- Grid layout с auto-fill
- Aspect ratio 1:1
- Масштабирование изображений при hover
- Плавное появление подписей снизу

#### Responsive design
- Mobile-first подход
- Адаптивные grid колонки
- Отключение hover на мобильных

---

## 📊 Визуализация данных

### charts.js - ISSVisualizer

#### Интерактивная карта
```javascript
// Leaflet с тёмной темой CartoDB
L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png')
```

Функции:
- **Маркер МКС** - кастомная иконка 🛰️ с popup
- **Траектория** - polyline последних 100 точек
- **Auto-update** - обновление каждые 10 секунд
- **Плавное движение** - panTo с анимацией

#### Графики Chart.js

**Velocity Chart:**
- Line график скорости за 24 часа
- Голубой цвет (#0d6efd)
- Gradient fill
- Без точек (плавная линия)

**Altitude Chart:**
- Line график высоты за 24 часа  
- Зелёный цвет (#198754)
- Gradient fill
- Auto-refresh каждую минуту

#### Real-time обновления
```javascript
// Обновление позиции каждые 10 сек
setInterval(() => this.updatePosition(), 10000);

// Обновление графиков каждую минуту
setInterval(() => this.updateCharts(), 60000);
```

### charts.js - OSDRVisualizer

**Doughnut Chart:**
- Группировка датасетов по типу
- 6 цветов для категорий
- Legend снизу

---

## 💻 JavaScript модули

### ui.js - CassiopeiaUI

#### Lazy Loading
```javascript
const imageObserver = new IntersectionObserver((entries, observer) => {
    if (entry.isIntersecting) {
        img.src = img.dataset.src;
        img.classList.add('loaded');
    }
});
```

#### Skeleton Loaders
- Автоматическое скрытие через 1 секунду
- Показ во время загрузки JWST

#### Smooth Scroll
- Плавная прокрутка к якорям
- Native `scroll-behavior: smooth`

#### Card Animations
- IntersectionObserver для появления
- Stagger effect (задержка 100ms между карточками)

#### Metrics Updater
- Анимация счётчика чисел
- Easing function (easeOutQuart)
- Duration: 1.5 секунды

#### Утилиты
- `showLoading()` / `hideLoading()` - overlay
- `showToast(message, type)` - уведомления
- `createToastContainer()` - контейнер для toast

### ui.js - JWSTGallery

#### Загрузка изображений
```javascript
async load(append = false) {
    if (!append) this.showSkeletons();
    
    const response = await fetch(`/api/jwst/feed?${params}`);
    const data = await response.json();
    
    data.items.forEach((item, index) => {
        this.addItem(item, index);
    });
}
```

#### Фильтрация
```javascript
setFilter(key, value) {
    this.filters[key] = value;
    this.currentPage = 1;
    this.load(false);
}
```

#### Lightbox
- Bootstrap Modal
- Полноразмерное изображение
- Подпись с метаданными

#### Infinite scroll
```javascript
nextPage() {
    this.currentPage++;
    this.load(true); // append mode
}
```

---

## 🎯 Метрики и улучшения

### Performance
- ✅ Lazy loading изображений (экономия трафика)
- ✅ Redis кеширование в RustApiService (5 минут)
- ✅ Скелетоны вместо пустых страниц
- ✅ Debounce для фильтров

### UX
- ✅ Fade-in анимации с задержкой
- ✅ Hover эффекты на карточках
- ✅ Loading states
- ✅ Toast уведомления
- ✅ Smooth scroll

### Accessibility
- ✅ Семантичная разметка
- ✅ ARIA атрибуты в toast
- ✅ Keyboard navigation для модалов
- ✅ Alt текст для изображений

---

## 📦 Структура файлов

```
services/php-web/laravel-patches/
├── app/
│   ├── Services/
│   │   ├── RustApiService.php      # Базовый API клиент
│   │   ├── IssService.php          # ISS данные
│   │   ├── OsdrService.php         # OSDR датасеты
│   │   └── JwstService.php         # JWST изображения
│   └── Http/Controllers/
│       ├── DashboardController.php # Dashboard (DI)
│       └── IssController.php       # ISS tracking (DI)
├── resources/views/
│   ├── assets/
│   │   ├── animations.css          # Анимации и стили
│   │   ├── ui.js                   # UI interactions
│   │   └── charts.js               # Data visualization
│   ├── layouts/
│   │   └── app.blade.php           # Обновлённый layout
│   └── dashboard.blade.php         # Улучшенный dashboard
└── routes/
    └── web.php                     # Чистые API роуты
```

---

## 🚀 API Endpoints

### ISS
- `GET /api/iss/last` - последняя позиция
- `GET /api/iss/trend?hours=24` - тренд движения
- `GET /api/iss/range?from=&to=` - диапазон

### OSDR
- `GET /api/osdr/list?page=1&per_page=20` - список датасетов
- `GET /api/osdr/stats` - статистика
- `GET /api/osdr/sync` - синхронизация

### JWST
- `GET /api/jwst/feed?source=jpg&instrument=NIRCam&page=1&perPage=24`

---

## 🎨 Дизайн-система

### Цвета
- Primary: `#0d6efd` (ISS элементы)
- Success: `#198754` (OSDR элементы)
- Warning: `#ffc107` (Alerts)
- Danger: `#dc3545` (Errors)

### Типография
- Headers: System font stack
- Metrics: `fs-3` (2.5rem), `fw-bold`
- Captions: `small`, `text-muted`

### Spacing
- Card gap: `1rem`
- Section margin: `mb-3`
- Animation delays: 0.1s, 0.2s, 0.3s

### Shadows
- Cards: `shadow-sm`
- Hover: `0 12px 24px rgba(0, 0, 0, 0.15)`

---

## ✨ Highlights

1. **Clean Architecture** - Service layer, DI, separation of concerns
2. **Modern UI** - Animations, skeleton loaders, smooth transitions
3. **Real-time** - Auto-updating ISS position and charts
4. **Performance** - Lazy loading, caching, optimized rendering
5. **Accessibility** - Semantic HTML, ARIA, keyboard navigation

---

## 📈 Результаты

- **Скорость загрузки**: ⬆️ Lazy loading снизил initial load
- **UX**: ⬆️ Анимации и feedback улучшили восприятие
- **Поддерживаемость**: ⬆️ Service layer упростил код
- **Масштабируемость**: ⬆️ Модульная архитектура

---

**Cassiopeia** - теперь с современным, отзывчивым и красивым интерфейсом! 🌌
